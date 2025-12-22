#!/usr/bin/env python3
import os
from app import create_app

app = create_app()

if __name__ == '__main__':
    # 开发环境配置
    app.run(
        host='0.0.0.0',
        port=int(os.getenv('PORT', 5000)),
        debug=os.getenv('DEBUG', 'True').lower() == 'true'
    )