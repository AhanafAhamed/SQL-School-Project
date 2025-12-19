# Library Management System

A minimalistic, fully functional Library Management System built with Flask and SQLAlchemy.

## Features

- **Staff Authentication**: Secure login system with password hashing.
- **Inventory Management**: Track books, authors, and categories.
- **Member Management**: Manage member records and view their borrowing history.
- **Loan System**: Issue and return books with automatic inventory updates.
- **Search & Filtering**: Search books by title/ISBN and members by name/email.
- **Reports**: View active loans and overdue books (via SQL Views).
- **Responsive UI**: Minimalist design with light/dark mode support.

## Tech Stack

- **Backend**: PHP, MySQL
- **Frontend**: HTML5, CSS3 (Vanilla), JS

## Installation (Create a Symbolic Link)

Creates a "virtual shortcut" in your `xampp\htdocs\library` folder that points to your project.

1.  Open **Command Prompt** as **Administrator**.
2.  Run the following command:
    ```cmd
    mklink /D "C:\xampp\htdocs\library" "<your folders>\SQL-School-Project\php"
    ```
3.  Now you can access the project at: `http://localhost/library`

## Default Credentials

The system comes with a pre-seeded admin account for initial setup:

| Role  | Username | Password   |
| ----- | -------- | ---------- |
| Admin | `admin`  | `admin123` |
| Staff | `alice`  | `staff123` |

## Project Structure
Entire code is self contained in the `php` folder.

ER diagram: 
![ER Diagram](Docs/ER.png)

