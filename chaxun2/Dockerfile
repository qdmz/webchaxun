FROM python:3.12-slim

WORKDIR /app

ENV PYTHONDONTWRITEBYTECODE=1
ENV PYTHONUNBUFFERED=1

RUN apt-get update && apt-get install -y build-essential gcc libpq-dev --no-install-recommends \
    && rm -rf /var/lib/apt/lists/*

COPY webchaxun/requirements.txt ./requirements.txt
RUN pip install --no-cache-dir -r requirements.txt

COPY webchaxun /app/webchaxun
WORKDIR /app/webchaxun

RUN mkdir -p /app/webchaxun/uploads

EXPOSE 8000

CMD ["gunicorn", "-w", "4", "-b", "0.0.0.0:8000", "app:app"]
