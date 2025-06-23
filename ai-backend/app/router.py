from fastapi import APIRouter, Request
from fastapi.responses import HTMLResponse, JSONResponse
from app.langchain import get_ai_response
from app.db import get_all_stories, add_story, get_all_users, add_user, get_user_profile, get_user_recent_stories
from app.utils import verify_password, create_access_token, verify_access_token
import mysql.connector
import os

router = APIRouter()

def row_to_dict(row):
    if isinstance(row, dict):
        return row
    if hasattr(row, '_fields') and hasattr(row, '_asdict'):
        return row._asdict()
    if hasattr(row, 'keys') and hasattr(row, '__getitem__'):
        return {k: row[k] for k in row.keys()}
    return dict(row) if row else None

@router.post("/login")
async def login(request: Request):
    data = await request.json()
    username = data.get("username")
    email = data.get("email")
    password = data.get("password")
    if not password or (not username and not email):
        return JSONResponse(content={"error": "Username/email and password required"}, status_code=400)
    # Connect to DB
    conn = mysql.connector.connect(
        host=os.environ.get("MYSQL_HOST", "localhost"),
        user=os.environ.get("MYSQL_USER", "root"),
        password=os.environ.get("MYSQL_PASSWORD", ""),
        database=os.environ.get("MYSQL_DATABASE", "storynest")
    )
    cursor = conn.cursor(dictionary=True)
    if email:
        cursor.execute("SELECT * FROM users WHERE email = %s", (email,))
    else:
        cursor.execute("SELECT * FROM users WHERE username = %s", (username,))
    user = cursor.fetchone()
    cursor.close()
    conn.close()
    user = row_to_dict(user)
    if not user:
        return JSONResponse(content={"error": "Invalid credentials"}, status_code=401)
    if not user.get("password") or not verify_password(password, str(user["password"])):
        return JSONResponse(content={"error": "Invalid credentials"}, status_code=401)
    token = create_access_token({"user_id": user["id"]})
    return JSONResponse(content={"access_token": token, "token_type": "bearer"})

@router.post("/chat")
async def chat(request: Request):
    try:
        data = await request.json()
        user_message = data.get("message", "").strip()
        # Extract JWT from Authorization header
        auth_header = request.headers.get("authorization")
        user_id = None
        if auth_header and auth_header.lower().startswith("bearer "):
            token = auth_header.split(" ", 1)[1]
            user_id = verify_access_token(token)
        if not user_message:
            return JSONResponse(content={"error": "No message provided"}, status_code=400)
        user_profile = None
        recent_stories = None
        if user_id:
            user_profile = await get_user_profile(user_id)
            user_profile = row_to_dict(user_profile)
            recent_stories = await get_user_recent_stories(user_id, limit=3)
        ai_response = get_ai_response(user_message, user_profile, recent_stories)
        return JSONResponse(content=ai_response)
    except Exception as e:
        return JSONResponse(content={"error": str(e)}, status_code=500)

@router.get("/stories")
async def stories():
    stories = await get_all_stories()
    return JSONResponse(content={"stories": stories})

@router.post("/stories")
async def create_story(request: Request):
    data = await request.json()
    result = await add_story(data)
    return JSONResponse(content=result)

@router.get("/users")
async def users():
    users = await get_all_users()
    return JSONResponse(content={"users": users})

@router.post("/users")
async def create_user(request: Request):
    data = await request.json()
    result = await add_user(data)
    return JSONResponse(content=result)

@router.get("/", response_class=HTMLResponse)
async def root():
    return """
    <!DOCTYPE html>
    <html>
    <head>
        <title>StoryNest AI Chat</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 0; }
            #chat-widget-btn {
                position: fixed; bottom: 24px; right: 24px; background: #6c47ff; color: #fff; border: none; border-radius: 50%; width: 60px; height: 60px; font-size: 30px; box-shadow: 0 4px 16px rgba(0,0,0,0.2); cursor: pointer; z-index: 1000;
            }
            #chat-widget {
                display: none; position: fixed; bottom: 94px; right: 24px; width: 350px; max-width: 90vw; height: 500px; background: #fff; border-radius: 16px; box-shadow: 0 4px 32px rgba(0,0,0,0.18); z-index: 1001; flex-direction: column; overflow: hidden;
            }
            #chat-header { background: #6c47ff; color: #fff; padding: 16px; font-weight: bold; }
            #chat-messages { flex: 1; padding: 16px; overflow-y: auto; background: #f7f7fa; }
            #chat-input-container { display: flex; border-top: 1px solid #eee; }
            #chat-input { flex: 1; padding: 10px; border: none; outline: none; }
            #chat-send { background: #6c47ff; color: #fff; border: none; padding: 0 20px; cursor: pointer; }
        </style>
    </head>
    <body>
        <button id="chat-widget-btn">💬</button>
        <div id="chat-widget">
            <div id="chat-header">StoryNest AI</div>
            <div id="chat-messages"></div>
            <div id="chat-input-container">
                <input id="chat-input" type="text" placeholder="Type your message..." />
                <button id="chat-send">Send</button>
            </div>
        </div>
        <script>
            const widgetBtn = document.getElementById('chat-widget-btn');
            const widget = document.getElementById('chat-widget');
            const messagesDiv = document.getElementById('chat-messages');
            const input = document.getElementById('chat-input');
            const sendBtn = document.getElementById('chat-send');
            widgetBtn.onclick = () => { widget.style.display = widget.style.display === 'flex' ? 'none' : 'flex'; widget.style.flexDirection = 'column'; };
            sendBtn.onclick = sendMessage;
            input.addEventListener('keypress', function(e) { if (e.key === 'Enter') sendMessage(); });
            function appendMessage(text, sender) {
                const msg = document.createElement('div');
                msg.textContent = (sender === 'user' ? 'You: ' : 'AI: ') + text;
                msg.style.margin = '8px 0';
                msg.style.color = sender === 'user' ? '#6c47ff' : '#222';
                messagesDiv.appendChild(msg);
                messagesDiv.scrollTop = messagesDiv.scrollHeight;
            }
            async function sendMessage() {
                const text = input.value.trim();
                if (!text) return;
                appendMessage(text, 'user');
                input.value = '';
                // Use JWT from login and send in Authorization header
                const token = localStorage.getItem('access_token');
                const headers = { 'Content-Type': 'application/json' };
                if (token) headers['Authorization'] = 'Bearer ' + token;
                try {
                    const res = await fetch('/chat', { method: 'POST', headers, body: JSON.stringify({ message: text }) });
                    const data = await res.json();
                    appendMessage(data.content || data.error || 'No response', 'ai');
                } catch (e) { appendMessage('Error contacting AI', 'ai'); }
            }
        </script>
    </body>
    </html>
    """ 