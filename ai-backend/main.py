import logging
import socket
from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from app.router import router
import uvicorn
import os
from langchain_groq import ChatGroq
from pydantic import SecretStr

# Setup basic logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

def get_local_ip():
    local_ip = "127.0.0.1"
    try:
        s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        s.connect(("8.8.8.8", 80))
        ip = s.getsockname()[0]
        s.close()
        if not ip.startswith('172.'):
            local_ip = ip
    except Exception as e:
        logger.warning(f"Could not detect non-loopback IP, defaulting to 127.0.0.1: {e}")
    return local_ip

host_ip = get_local_ip()
allowed_origins = [
    "http://localhost",
    "http://127.0.0.1",
    "http://localhost:8000",
    "http://127.0.0.1:8000",
    f"http://{host_ip}",
    f"http://{host_ip}:8000",
    "*"
]

logger.info(f"Allowed Origins for CORS: {allowed_origins}")

app = FastAPI()
app.include_router(router)

app.add_middleware(
    CORSMiddleware,
    allow_origins=allowed_origins,
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
    expose_headers=["*"]
)

if __name__ == "__main__":
    uvicorn.run(app, host="0.0.0.0", port=8000)