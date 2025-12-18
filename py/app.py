import os
from flask import Flask
from jinja2 import ChoiceLoader, FileSystemLoader
from extensions import db
from routes import main
from fill import run_seeding

def create_app():
    app = Flask(__name__, template_folder='..', static_folder='../Commons', static_url_path='/static')
    
    db_path = os.path.join(app.root_path, 'instance', 'library.db')
    if not os.path.exists(os.path.join(app.root_path, 'instance')):
        os.makedirs(os.path.join(app.root_path, 'instance'))
    
    app.config['SQLALCHEMY_DATABASE_URI'] = f'sqlite:///{db_path}'
    app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False
    app.secret_key = 'super-secret-key'
    
    db.init_app(app)
    
    app.jinja_loader = ChoiceLoader([
        FileSystemLoader('../templates'),
        FileSystemLoader('../Commons')
    ])
    
    app.register_blueprint(main)
    
    with app.app_context():
        if not os.path.exists(db_path):
            print(f"Database not found at {db_path}. Running automatic seeding...")
            run_seeding(app)
        else:
            db.create_all()
    
    return app

app = create_app()

if __name__ == '__main__':
    app.run(debug=True)
