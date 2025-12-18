from flask import Blueprint, render_template, request, redirect, url_for, jsonify, session, flash
from werkzeug.security import generate_password_hash, check_password_hash
from datetime import datetime, UTC
from functools import wraps
from extensions import db
from models import Author, Category, Book, Member, Staff, Loan

main = Blueprint('main', __name__)

# Auth Decorator
def login_required(f):
    @wraps(f)
    def decorated_function(*args, **kwargs):
        if 'staff_id' not in session:
            flash("Please log in first.")
            return redirect(url_for('main.login'))
        return f(*args, **kwargs)
    return decorated_function

def admin_required(f):
    @wraps(f)
    def decorated_function(*args, **kwargs):
        if 'staff_id' not in session:
            flash("Please log in first.")
            return redirect(url_for('main.login'))
        if session.get('staff_role') != 'Admin':
            flash("You do not have permission to access this page.")
            return redirect(url_for('main.index'))
        return f(*args, **kwargs)
    return decorated_function

@main.route('/reports/overdue')
@login_required
def overdue_report():
    result = db.session.execute(db.text("SELECT * FROM overdue_books")).fetchall()
    return render_template('overdue.html', overdue=result)

@main.route('/members/<id>/history')
@login_required
def member_history(id):
    member = Member.query.get_or_404(id)
    loan_count = Loan.query.filter_by(member_id=id).count()
    active_loans = Loan.query.filter_by(member_id=id, return_date=None).count()
    return render_template('member_history.html', member=member, loan_count=loan_count, active_loans=active_loans)

@main.route('/login', methods=['GET', 'POST'])
def login():
    if request.method == 'POST':
        username = request.form.get('username')
        password = request.form.get('password')
        staff = Staff.query.filter_by(username=username).first()
        if staff and check_password_hash(staff.password, password):
            session['staff_id'] = staff.staff_id
            session['staff_name'] = staff.name
            session['staff_role'] = staff.role
            return redirect(url_for('main.index'))
        flash("Invalid credentials")
    return render_template('login.html')

@main.route('/logout')
def logout():
    session.clear()
    return redirect(url_for('main.login'))

@main.route('/staff/register', methods=['GET', 'POST'])
@admin_required
def register_staff():
    if request.method == 'POST':
        name = request.form.get('name')
        username = request.form.get('username')
        password = request.form.get('password')
        role = request.form.get('role', 'Staff')
        
        if Staff.query.filter_by(username=username).first():
            flash("Username already exists!")
        else:
            new_staff = Staff(
                name=name,
                username=username,
                password=generate_password_hash(password),
                role=role
            )
            db.session.add(new_staff)
            db.session.commit()
            flash(f"Staff account created for {username}")
            return redirect(url_for('main.index'))
            
    return render_template('register_staff.html')

@main.route('/')
@login_required
def index():
    book_count = Book.query.count()
    member_count = Member.query.count()
    loan_count = Loan.query.filter(Loan.return_date == None).count()
    
    popular_books_query = """
        SELECT title, loan_count FROM (
            SELECT b.title, COUNT(l.loan_id) as loan_count
            FROM books b
            LEFT JOIN loans l ON b.book_id = l.book_id
            GROUP BY b.book_id
        ) ORDER BY loan_count DESC LIMIT 5
    """
    popular_books = db.session.execute(db.text(popular_books_query)).fetchall()
    
    return render_template('index.html', 
                           book_count=book_count, 
                           member_count=member_count, 
                           loan_count=loan_count,
                           popular_books=popular_books)

@main.route('/books')
@login_required
def books():
    search = request.args.get('search', '')
    query = "SELECT * FROM book_details_view"
    params = {}
    if search:
        query += " WHERE title LIKE :s OR isbn LIKE :s"
        params['s'] = f"%{search}%"
    
    result = db.session.execute(db.text(query), params).mappings().all()
    return render_template('books.html', books=result)

@main.route('/books/add', methods=['GET', 'POST'])
@login_required
def add_book():
    if request.method == 'POST':
        new_book = Book(
            title=request.form['title'],
            category_id=request.form['category_id'],
            author_id=request.form['author_id'],
            published_year=request.form['published_year'],
            isbn=request.form['isbn'],
            total_stock=int(request.form['total_stock']),
            total_available=int(request.form['total_stock'])
        )
        db.session.add(new_book)
        db.session.commit()
        flash("Book added successfully!")
        return redirect(url_for('main.books'))
    
    authors = Author.query.all()
    categories = Category.query.all()
    return render_template('edit_book.html', authors=authors, categories=categories, book=None)

@main.route('/books/edit/<id>', methods=['GET', 'POST'])
@login_required
def edit_book(id):
    book = Book.query.get_or_404(id)
    if request.method == 'POST':
        book.title = request.form['title']
        book.category_id = request.form['category_id']
        book.author_id = request.form['author_id']
        book.published_year = request.form['published_year']
        book.isbn = request.form['isbn']
        
        new_stock = int(request.form['total_stock'])
        active_loans = book.total_stock - book.total_available
        book.total_stock = new_stock
        book.total_available = new_stock - active_loans
        
        db.session.commit()
        flash("Book updated successfully!")
        return redirect(url_for('main.books'))
    
    authors = Author.query.all()
    categories = Category.query.all()
    return render_template('edit_book.html', authors=authors, categories=categories, book=book)

@main.route('/books/delete/<id>', methods=['POST'])
@login_required
def delete_book(id):
    book = Book.query.get_or_404(id)
    db.session.delete(book)
    db.session.commit()
    flash("Book deleted!")
    return redirect(url_for('main.books'))

@main.route('/loans')
@login_required
def loans():
    today = datetime.now(UTC).date().isoformat()
    query = "SELECT * FROM loan_details_view"
    all_loans = db.session.execute(db.text(query)).mappings().all()
    
    loans_list = list(all_loans)
    loans_list.sort(key=lambda x: (
        x['return_date'] is not None,
        x['due_date'] >= today if x['return_date'] is None else True, 
        x['due_date']
    ))
    
    return render_template('loans.html', loans=loans_list, today=today)

@main.route('/members')
@login_required
def members():
    search = request.args.get('search', '')
    if search:
        all_members = Member.query.filter(Member.member_name.contains(search) | Member.email.contains(search)).all()
    else:
        all_members = Member.query.all()
    return render_template('members.html', members=all_members)

@main.route('/members/add', methods=['GET', 'POST'])
@login_required
def add_member():
    if request.method == 'POST':
        new_member = Member(
            member_name=request.form['member_name'],
            email=request.form['email'],
            phone=request.form['phone']
        )
        db.session.add(new_member)
        db.session.commit()
        flash("Member added!")
        return redirect(url_for('main.members'))
    return render_template('edit_member.html', member=None)

@main.route('/members/edit/<id>', methods=['GET', 'POST'])
@login_required
def edit_member(id):
    member = Member.query.get_or_404(id)
    if request.method == 'POST':
        member.member_name = request.form['member_name']
        member.email = request.form['email']
        member.phone = request.form['phone']
        db.session.commit()
        flash("Member updated!")
        return redirect(url_for('main.members'))
    return render_template('edit_member.html', member=member)

@main.route('/loans/issue', methods=['GET', 'POST'])
@login_required
def issue_loan():
    if request.method == 'POST':
        book_id = request.form['book_id']
        member_id = request.form['member_id']
        book = db.session.get(Book, book_id)
        
        if book and book.total_available > 0:
            loan = Loan(
                book_id=book_id,
                member_id=member_id,
                staff_id=session['staff_id'],
                due_date=datetime.strptime(request.form['due_date'], '%Y-%m-%d').date()
            )
            book.total_available -= 1
            book.total_loaned += 1
            db.session.add(loan)
            db.session.commit()
            flash("Book issued successfully!")
        else:
            flash("Error: Book not available.")
        return redirect(url_for('main.loans'))
    
    books = Book.query.filter(Book.total_available > 0).all()
    members = Member.query.all()
    return render_template('issue_loan.html', books=books, members=members)

@main.route('/loans/edit/<id>', methods=['GET', 'POST'])
@login_required
def edit_loan(id):
    loan = Loan.query.get_or_404(id)
    if request.method == 'POST':
        loan.loan_date = datetime.strptime(request.form['loan_date'], '%Y-%m-%d').date()
        loan.due_date = datetime.strptime(request.form['due_date'], '%Y-%m-%d').date()
        if request.form['return_date']:
            loan.return_date = datetime.strptime(request.form['return_date'], '%Y-%m-%d').date()
        else:
            loan.return_date = None
        loan.member_id = request.form['member_id']
        loan.book_id = request.form['book_id']
        db.session.commit()
        flash("Loan updated!")
        return redirect(url_for('main.loans'))
    
    books = Book.query.all()
    members = Member.query.all()
    return render_template('edit_loan.html', loan=loan, books=books, members=members)

@main.route('/loans/return/<id>', methods=['POST'])
@login_required
def return_loan(id):
    loan = Loan.query.get_or_404(id)
    if not loan.return_date:
        loan.return_date = datetime.now(UTC).date()
        book = db.session.get(Book, loan.book_id)
        book.total_available += 1
        book.total_loaned -= 1
        db.session.commit()
        flash("Book returned!")
    return redirect(url_for('main.loans'))

@main.route('/authors', methods=['GET', 'POST'])
@login_required
def authors():
    if request.method == 'POST':
        new_author = Author(author_name=request.form['name'])
        db.session.add(new_author)
        db.session.commit()
        flash("Author added!")
    all_authors = Author.query.all()
    return render_template('authors_cats.html', items=all_authors, type='Author', edit_item=None)

@main.route('/authors/edit/<id>', methods=['GET', 'POST'])
@login_required
def edit_author(id):
    author = Author.query.get_or_404(id)
    if request.method == 'POST':
        author.author_name = request.form['name']
        db.session.commit()
        flash("Author updated!")
        return redirect(url_for('main.authors'))
    all_authors = Author.query.all()
    return render_template('authors_cats.html', items=all_authors, type='Author', edit_item=author)

@main.route('/categories', methods=['GET', 'POST'])
@login_required
def categories():
    if request.method == 'POST':
        new_cat = Category(category_name=request.form['name'])
        db.session.add(new_cat)
        db.session.commit()
        flash("Category added!")
    all_cats = Category.query.all()
    return render_template('authors_cats.html', items=all_cats, type='Category', edit_item=None)

@main.route('/categories/edit/<id>', methods=['GET', 'POST'])
@login_required
def edit_category(id):
    cat = Category.query.get_or_404(id)
    if request.method == 'POST':
        cat.category_name = request.form['name']
        db.session.commit()
        flash("Category updated!")
        return redirect(url_for('main.categories'))
    all_cats = Category.query.all()
    return render_template('authors_cats.html', items=all_cats, type='Category', edit_item=cat)

@main.route('/authors/delete/<id>', methods=['POST'])
@login_required
def delete_author(id):
    author = Author.query.get_or_404(id)
    db.session.delete(author)
    db.session.commit()
    flash("Author deleted!")
    return redirect(url_for('main.authors'))

@main.route('/categories/delete/<id>', methods=['POST'])
@login_required
def delete_category(id):
    cat = Category.query.get_or_404(id)
    db.session.delete(cat)
    db.session.commit()
    flash("Category deleted!")
    return redirect(url_for('main.categories'))
