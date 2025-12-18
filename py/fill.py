import random
import os
from datetime import datetime, timedelta, UTC
from werkzeug.security import generate_password_hash
from extensions import db
from models import Author, Category, Book, Member, Staff, Loan

# Configuration
NUM_AUTHORS = 50
NUM_BOOKS = 2500
NUM_MEMBERS = 200
NUM_LOANS = 1500

def run_seeding(app):
    with app.app_context():
        print("Initializing database...")
        db.create_all()
        
        # Check if already seeded
        if Staff.query.first():
            print("Database already contains data. Skipping seeding.")
            return

        # Redo Views
        db.session.execute(db.text("DROP VIEW IF EXISTS overdue_books"))
        db.session.execute(db.text("""
            CREATE VIEW overdue_books AS
            SELECT l.loan_id, b.title, m.member_name, l.loan_date, l.due_date
            FROM loans l
            LEFT JOIN books b ON l.book_id = b.book_id
            LEFT JOIN members m ON l.member_id = m.member_id
            WHERE l.return_date IS NULL 
            AND julianday('now', 'localtime') > julianday(l.due_date)
        """))

        db.session.execute(db.text("DROP VIEW IF EXISTS book_details_view"))
        db.session.execute(db.text("""
            CREATE VIEW book_details_view AS
            SELECT b.book_id, b.title, a.author_name, c.category_name, b.isbn, b.total_stock, b.total_available
            FROM books b
            LEFT JOIN authors a ON b.author_id = a.author_id
            LEFT JOIN categories c ON b.category_id = c.category_id
        """))

        db.session.execute(db.text("DROP VIEW IF EXISTS loan_details_view"))
        db.session.execute(db.text("""
            CREATE VIEW loan_details_view AS
            SELECT l.loan_id, b.title AS book_title, m.member_name, s.name AS staff_name, s.username AS staff_username,
                   l.loan_date, l.due_date, l.return_date, l.book_id, l.member_id
            FROM loans l
            LEFT JOIN books b ON l.book_id = b.book_id
            LEFT JOIN members m ON l.member_id = m.member_id
            LEFT JOIN staff s ON l.staff_id = s.staff_id
        """))
        db.session.commit()

        print("Seeding Staff...")
        admin = Staff(name="Admin", username="admin", password=generate_password_hash("admin123"), role="Admin")
        staff1 = Staff(name="Alice Librarian", username="alice", password=generate_password_hash("staff123"), role="Staff")
        db.session.add_all([admin, staff1])
        db.session.commit()

        print("Seeding Categories...")
        cat_names = ["Fiction", "Non-Fiction", "Science", "History", "Technology", "Biography", "Fantasy", "Mystery", "Romance", "Self-Help"]
        categories = [Category(category_name=name) for name in cat_names]
        db.session.add_all(categories)
        db.session.commit()

        print("Seeding Authors...")
        authors = [Author(author_name=f"Author {i}") for i in range(1, NUM_AUTHORS + 1)]
        db.session.add_all(authors)
        db.session.commit()

        print(f"Seeding {NUM_BOOKS} Books...")
        book_titles = ["The Silent", "Echoes of", "Rising", "Lost in", "Secrets of", "Midnight", "Journey to", "Eternal", "Shadows", "Beyond the"]
        book_nouns = ["Ocean", "Forest", "Empire", "Time", "Desert", "City", "Galaxy", "Soul", "Heart", "Legacy"]
        
        books = []
        for i in range(NUM_BOOKS):
            title = f"{random.choice(book_titles)} {random.choice(book_nouns)} Part {i+1}"
            stock = random.randint(5, 50)
            book = Book(
                title=title,
                category_id=random.choice(categories).category_id,
                author_id=random.choice(authors).author_id,
                published_year=random.randint(1950, 2024),
                isbn=f"{random.randint(100, 999)}-{random.randint(1000, 9999)}",
                total_stock=stock,
                total_available=stock
            )
            books.append(book)
        db.session.add_all(books)
        db.session.commit()

        print(f"Seeding {NUM_MEMBERS} Members...")
        members = []
        for i in range(NUM_MEMBERS):
            member = Member(
                member_name=f"Member {i+1}",
                email=f"member{i+1}@example.com",
                phone=f"555-{random.randint(1000, 9999)}"
            )
            members.append(member)
        db.session.add_all(members)
        db.session.commit()

        print(f"Seeding {NUM_LOANS} Loans...")
        staff_ids = [admin.staff_id, staff1.staff_id]
        for _ in range(NUM_LOANS):
            book = random.choice(books)
            if book.total_available > 0:
                loan_date = datetime.now(UTC) - timedelta(days=random.randint(1, 100))
                due_date = loan_date + timedelta(days=14)
                
                return_date = None
                if random.random() > 0.3:
                    return_date = loan_date + timedelta(days=random.randint(1, 13))
                else:
                    book.total_available -= 1
                    book.total_loaned += 1

                loan = Loan(
                    book_id=book.book_id,
                    member_id=random.choice(members).member_id,
                    staff_id=random.choice(staff_ids),
                    loan_date=loan_date.date(),
                    due_date=due_date.date(),
                    return_date=return_date.date() if return_date else None
                )
                db.session.add(loan)
        
        db.session.commit()
        print("Database seeded successfully!")

if __name__ == "__main__":
    from flask import Flask
    import os
    # Standalone mode
    app = Flask(__name__)
    app.config['SQLALCHEMY_DATABASE_URI'] = 'sqlite:///library.db'
    app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False
    db.init_app(app)
    # Check if DB file exists and delete it for a fresh seed in standalone mode
    db_path = 'library.db'
    if os.path.exists(db_path):
        os.remove(db_path)
    run_seeding(app)
