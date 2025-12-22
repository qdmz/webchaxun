from setuptools import setup, find_packages

setup(
    name="webchaxun",
    version="1.0.0",
    description="Excel数据查询与分析平台",
    author="qdmz",
    author_email="qdmz@vip.qq.com",
    packages=find_packages(),
    include_package_data=True,
    zip_safe=False,
    install_requires=[
        "Flask==2.3.3",
        "Flask-SQLAlchemy==3.0.5",
        "Flask-Login==0.6.3",
        "Flask-WTF==1.1.1",
        "Werkzeug==2.3.7",
        "pandas==2.1.1",
        "openpyxl==3.1.2",
        "xlrd==2.0.1",
        "WTForms==3.0.1",
        "bcrypt==4.0.1",
        "python-dotenv==1.0.0",
        "gunicorn==21.2.0"
    ],
    classifiers=[
        "Development Status :: 4 - Beta",
        "Intended Audience :: Developers",
        "License :: OSI Approved :: MIT License",
        "Programming Language :: Python :: 3",
        "Programming Language :: Python :: 3.8",
        "Programming Language :: Python :: 3.9",
        "Programming Language :: Python :: 3.10",
        "Programming Language :: Python :: 3.11",
    ],
    python_requires=">=3.8",
)