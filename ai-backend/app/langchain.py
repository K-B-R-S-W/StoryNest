from dotenv import load_dotenv
load_dotenv()
import os
from langchain_groq import ChatGroq
from langchain_core.messages import HumanMessage, SystemMessage
from pydantic import SecretStr
from typing import Optional

SYSTEM_PROMPT = """
You are StoryNest AI, a creative and supportive writing assistant for the StoryNest platform. Your job is to help users with story ideas, writing advice, editing, and platform questions.

Key Features:
- Brainstorm story ideas, characters, and plots in genres like Fantasy, Sci-Fi, Mystery, Romance, Horror, Thriller, Historical Fiction, Poetry, and Non-Fiction.
- Give constructive feedback and editing suggestions for stories, chapters, or poems.
- Answer questions about writing techniques, genres, and the StoryNest platform (e.g., challenges, achievements, categories).
- Encourage creativity and support writers of all levels.
- If asked about platform features, explain things like challenges, achievements, bookmarks, series, and comments.
- Be positive, creative, and helpful.

Example interactions:
User: "Help me come up with a fantasy story idea."
AI: "How about a world where dreams shape reality, and a young dreamer must save their city from a nightmare invasion?"

User: "How do I improve my dialogue?"
AI: "Try to make each character's voice unique, use subtext, and keep conversations purposeful. Would you like an example?"

User: "What are StoryNest challenges?"
AI: "Challenges are themed writing contests where you can submit stories, earn achievements, and get feedback from the community."
"""

groq_api_key = os.environ.get("GROQ_API_KEY")
if not groq_api_key:
    raise ValueError("GROQ_API_KEY not found in environment variables. Please check your .env file.")
groq_api_key_secret = SecretStr(groq_api_key)
model = ChatGroq(model="llama3-8b-8192", api_key=groq_api_key_secret)

def build_personal_context(user_profile, recent_stories):
    context = ""
    if user_profile:
        context += f"User Profile:\nName: {user_profile.get('display_name') or user_profile.get('username')}\n"
        if user_profile.get('bio'):
            context += f"Bio: {user_profile['bio']}\n"
        if user_profile.get('website'):
            context += f"Website: {user_profile['website']}\n"
        if user_profile.get('twitter'):
            context += f"Twitter: {user_profile['twitter']}\n"
        if user_profile.get('instagram'):
            context += f"Instagram: {user_profile['instagram']}\n"
    if recent_stories:
        context += "Recent Stories:\n"
        for s in recent_stories:
            context += f"- {s['title']} ({s['status']}, {s['created_at'].strftime('%Y-%m-%d') if hasattr(s['created_at'], 'strftime') else s['created_at']})\n"
            if s.get('excerpt'):
                context += f"  Excerpt: {s['excerpt']}\n"
    return context.strip()

def get_ai_response(user_message: str, user_profile: Optional[dict] = None, recent_stories: Optional[list] = None):
    context = build_personal_context(user_profile, recent_stories)
    prompt = (context + "\n\n" if context else "") + SYSTEM_PROMPT
    messages = [SystemMessage(content=prompt), HumanMessage(content=user_message)]
    try:
        response = model.invoke(messages)
        return {"content": response.content}
    except Exception as e:
        return {"error": str(e)} 