import os
import mysql.connector
from mysql.connector import Error
from fastapi import HTTPException
import asyncio

__all__ = [
    'get_all_stories', 'add_story', 'get_all_users', 'add_user',
    'get_user_profile', 'get_user_recent_stories'
]

def get_db_connection():
    return mysql.connector.connect(
        host=os.environ.get("MYSQL_HOST", "localhost"),
        user=os.environ.get("MYSQL_USER", "root"),
        password=os.environ.get("MYSQL_PASSWORD", ""),
        database=os.environ.get("MYSQL_DATABASE", "storynest")
    )

async def get_all_stories():
    try:
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT * FROM stories")
        stories = cursor.fetchall()
        cursor.close()
        conn.close()
        return stories
    except Error as e:
        raise HTTPException(status_code=500, detail=str(e))

async def add_story(data):
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute(
            "INSERT INTO stories (user_id, title, content, category_id, status) VALUES (%s, %s, %s, %s, %s)",
            (data.get("user_id"), data.get("title"), data.get("content"), data.get("category_id"), data.get("status", "draft"))
        )
        conn.commit()
        story_id = cursor.lastrowid
        cursor.close()
        conn.close()
        return {"id": story_id, "message": "Story added successfully"}
    except Error as e:
        raise HTTPException(status_code=500, detail=str(e))

async def get_all_users():
    try:
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT * FROM users")
        users = cursor.fetchall()
        cursor.close()
        conn.close()
        return users
    except Error as e:
        raise HTTPException(status_code=500, detail=str(e))

async def add_user(data):
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute(
            "INSERT INTO users (username, email, password, display_name) VALUES (%s, %s, %s, %s)",
            (data.get("username"), data.get("email"), data.get("password"), data.get("display_name"))
        )
        conn.commit()
        user_id = cursor.lastrowid
        cursor.close()
        conn.close()
        return {"id": user_id, "message": "User added successfully"}
    except Error as e:
        raise HTTPException(status_code=500, detail=str(e))

async def get_user_profile(user_id):
    try:
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT id, username, display_name, bio, profile_image, website, twitter, instagram, created_at FROM users WHERE id = %s", (user_id,))
        user = cursor.fetchone()
        cursor.close()
        conn.close()
        return user
    except Error as e:
        raise HTTPException(status_code=500, detail=str(e))

async def get_user_recent_stories(user_id, limit=3):
    try:
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT id, title, excerpt, created_at, status FROM stories WHERE user_id = %s ORDER BY created_at DESC LIMIT %s", (user_id, limit))
        stories = cursor.fetchall()
        cursor.close()
        conn.close()
        return stories
    except Error as e:
        raise HTTPException(status_code=500, detail=str(e)) 