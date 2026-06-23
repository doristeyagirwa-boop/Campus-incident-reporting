import mysql.connector
import random

# Connect to database
conn = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",
    database="incident_management"
)
cursor = conn.cursor()

# Sample categories
categories = ['Network', 'Hardware', 'Software', 'Security', 'Database']
for cat in categories:
    cursor.execute("INSERT IGNORE INTO categories (name) VALUES (%s)", (cat,))

# Sample users
first_names = [
    "Brian", "Faith", "Kevin", "Grace", "Dennis", "Mercy", "Victor", "Lydia",
    "Samuel", "Esther", "Daniel", "Winnie", "Joseph", "Caroline", "Michael",
    "Doris", "James", "Beatrice", "Peter", "Stella"
]

last_names = [
    "Kamau", "Otieno", "Mwangi", "Achieng", "Njoroge", "Odhiambo", "Kariuki",
    "Adhiambo", "Mutua", "Wanjiru", "Gitonga", "Awino", "Kimani", "Auma",
    "Waweru", "Ogola", "Ndungu", "Anyango", "Macharia", "Moraa"
]

# Insert admin
cursor.execute("""
    INSERT IGNORE INTO users (full_name, email, password, role)
    VALUES ('Admin User', 'admin@campus.ac.ke', 'hashed_password', 'admin')
""")

# Insert 20 students
for i in range(20):
    first = random.choice(first_names)
    last = random.choice(last_names)
    full_name = f"{first} {last}"
    email = f"{first.lower()}.{last.lower()}{i}@strathmore.com"
    cursor.execute("""
        INSERT IGNORE INTO users (full_name, email, password, role)
        VALUES (%s, %s, 'hashed_password', 'user')
    """, (full_name, email))

# Sample incidents
titles = [
    "Internet is down in block A",
    "Projector not working in room 101",
    "System keeps crashing",
    "Unauthorized access detected",
    "Database connection error",
    "WiFi slow in library",
    "Printer not working",
    "Power outage in lab",
    "Software license expired",
    "Network cable broken"
]

statuses = ['open', 'in_progress', 'resolved']
priorities = ['low', 'medium', 'high']

for i, title in enumerate(titles):
    cursor.execute("""
        INSERT IGNORE INTO incidents 
        (title, description, status, priority, category_id, reported_by)
        VALUES (%s, %s, %s, %s, %s, %s)
    """, (
        title,
        f"Description for: {title}",
        random.choice(statuses),
        random.choice(priorities),
        random.randint(1, 5),
        random.randint(1, 20)
    ))

# Sample comments
comments = [
    "This issue is still ongoing please fix it",
    "I have the same problem in block B",
    "This was resolved yesterday thanks",
    "Please prioritize this issue",
    "IT team is working on it"
]

for i in range(10):
    cursor.execute("""
        INSERT INTO comments (incident_id, user_id, comment_text)
        VALUES (%s, %s, %s)
    """, (
        random.randint(1, 10),
        random.randint(1, 20),
        random.choice(comments)
    ))

# Sample notifications
for i in range(10):
    cursor.execute("""
        INSERT INTO notifications (user_id, message)
        VALUES (%s, %s)
    """, (
        random.randint(1, 20),
        f"New update on incident #{random.randint(1, 10)}"
    ))

conn.commit()
print("✅ Sample data inserted successfully!")
cursor.close()
conn.close()