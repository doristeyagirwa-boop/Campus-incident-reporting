from flask import Flask, render_template, request, redirect, url_for, session, flash
from db import get_db
from datetime import datetime

app = Flask(__name__)
app.secret_key = "campus_incident_key"
# Pass notification count to all templates
@app.context_processor
def notification_count():
    user_id = session.get('user_id', 1)
    conn = get_db()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("""
        SELECT COUNT(*) as count FROM notifications 
        WHERE user_id = %s AND is_read = FALSE
    """, (user_id,))
    count = cursor.fetchone()['count']
    cursor.close()
    conn.close()
    return dict(notification_count=count)

# ─── HOME ───────────────────────────────────────────
@app.route('/')
def home():
    return redirect(url_for('reports'))

# ─── COMMENTS ───────────────────────────────────────
@app.route('/incident/<int:incident_id>/comments')
def view_comments(incident_id):
    conn = get_db()
    cursor = conn.cursor(dictionary=True)
    
    # Get incident details
    cursor.execute("SELECT * FROM incidents WHERE id = %s", (incident_id,))
    incident = cursor.fetchone()
    
    # Get all comments for this incident
    cursor.execute("""
        SELECT comments.*, users.full_name 
        FROM comments 
        JOIN users ON comments.user_id = users.id
        WHERE comments.incident_id = %s
        ORDER BY comments.created_at DESC
    """, (incident_id,))
    comments = cursor.fetchall()
    
    cursor.close()
    conn.close()
    return render_template('comments.html', incident=incident, comments=comments)

@app.route('/incident/<int:incident_id>/comments/add', methods=['POST'])
def add_comment(incident_id):
    comment_text = request.form['comment_text']
    user_id = session.get('user_id', 1)  # default to 1 for now
    
    conn = get_db()
    cursor = conn.cursor()
    cursor.execute("""
        INSERT INTO comments (incident_id, user_id, comment_text)
        VALUES (%s, %s, %s)
    """, (incident_id, user_id, comment_text))
    
    # Add notification
    cursor.execute("""
        INSERT INTO notifications (user_id, message)
        VALUES (%s, %s)
    """, (user_id, f"New comment added on incident #{incident_id}"))
    
    conn.commit()
    cursor.close()
    conn.close()
    flash("Comment added successfully!")
    return redirect(url_for('view_comments', incident_id=incident_id))

# ─── NOTIFICATIONS ──────────────────────────────────
@app.route('/notifications')
def notifications():
    user_id = session.get('user_id', 1)  # default to 1 for now
    
    conn = get_db()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("""
        SELECT * FROM notifications 
        WHERE user_id = %s 
        ORDER BY created_at DESC
    """, (user_id,))
    notifications = cursor.fetchall()
    
    # Mark all as read
    cursor.execute("""
        UPDATE notifications SET is_read = TRUE 
        WHERE user_id = %s
    """, (user_id,))
    
    conn.commit()
    cursor.close()
    conn.close()
    return render_template('notifications.html', notifications=notifications)

# ─── REPORTS ────────────────────────────────────────
@app.route('/reports')
def reports():
    conn = get_db()
    cursor = conn.cursor(dictionary=True)
    
    # Incidents by category
    cursor.execute("""
        SELECT categories.name, COUNT(incidents.id) as total
        FROM incidents
        JOIN categories ON incidents.category_id = categories.id
        GROUP BY categories.name
    """)
    by_category = cursor.fetchall()
    
    # Incidents by month
    cursor.execute("""
        SELECT MONTHNAME(created_at) as month, COUNT(id) as total
        FROM incidents
        GROUP BY MONTH(created_at), MONTHNAME(created_at)
        ORDER BY MONTH(created_at)
    """)
    by_month = cursor.fetchall()
    
    # Resolution rate
    cursor.execute("SELECT COUNT(*) as total FROM incidents")
    total = cursor.fetchone()['total']
    
    cursor.execute("SELECT COUNT(*) as resolved FROM incidents WHERE status = 'resolved'")
    resolved = cursor.fetchone()['resolved']
    
    unresolved = total - resolved
    
    cursor.close()
    conn.close()
    return render_template('reports.html', 
                           by_category=by_category,
                           by_month=by_month,
                           resolved=resolved,
                           unresolved=unresolved,
                           total=total)

if __name__ == '__main__':
    app.run(debug=True)