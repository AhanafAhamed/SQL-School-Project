import uuid
from datetime import datetime, UTC
from extensions import db

# Helper to generate unique IDs
def generate_id():
    return str(uuid.uuid4())[:8]

class Author(db.Model):
    __tablename__ = 'authors'
    author_id = db.Column(db.String(8), primary_key=True, default=generate_id)
    author_name = db.Column(db.String(100), nullable=False)
    books = db.relationship('Book', backref='author', lazy=True)

class Category(db.Model):
    __tablename__ = 'categories'
    category_id = db.Column(db.String(8), primary_key=True, default=generate_id)
    category_name = db.Column(db.String(100), nullable=False)
    books = db.relationship('Book', backref='category', lazy=True)

class Book(db.Model):
    __tablename__ = 'books'
    book_id = db.Column(db.String(8), primary_key=True, default=generate_id)
    title = db.Column(db.String(255), nullable=False)
    category_id = db.Column(db.String(8), db.ForeignKey('categories.category_id'))
    author_id = db.Column(db.String(8), db.ForeignKey('authors.author_id'))
    published_year = db.Column(db.Integer)
    isbn = db.Column(db.String(20))
    total_stock = db.Column(db.Integer, default=0)
    total_available = db.Column(db.Integer, default=0)
    total_loaned = db.Column(db.Integer, default=0)
    loans = db.relationship('Loan', backref='book', lazy=True)

class Member(db.Model):
    __tablename__ = 'members'
    member_id = db.Column(db.String(8), primary_key=True, default=generate_id)
    member_name = db.Column(db.String(100), nullable=False)
    email = db.Column(db.String(100))
    phone = db.Column(db.String(20))
    loans = db.relationship('Loan', backref='member', lazy=True)

class Staff(db.Model):
    __tablename__ = 'staff'
    staff_id = db.Column(db.String(8), primary_key=True, default=generate_id)
    name = db.Column(db.String(100), nullable=False)
    username = db.Column(db.String(50), unique=True, nullable=False)
    password = db.Column(db.String(100), nullable=False)
    role = db.Column(db.String(20))
    loans = db.relationship('Loan', backref='staff', lazy=True)

class Loan(db.Model):
    __tablename__ = 'loans'
    loan_id = db.Column(db.String(8), primary_key=True, default=generate_id)
    loan_date = db.Column(db.Date, default=lambda: datetime.now(UTC).date())
    due_date = db.Column(db.Date, nullable=False)
    return_date = db.Column(db.Date)
    staff_id = db.Column(db.String(8), db.ForeignKey('staff.staff_id'))
    book_id = db.Column(db.String(8), db.ForeignKey('books.book_id'))
    member_id = db.Column(db.String(8), db.ForeignKey('members.member_id'))
