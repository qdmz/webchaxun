from app import db, User, Config, app


def init():
    with app.app_context():
        db.create_all()
        if not User.query.filter_by(username='admin').first():
            u = User(username='admin', is_admin=True)
            u.set_password('admin')
            db.session.add(u)
        if not Config.query.get('open_registration'):
            cfg = Config(key='open_registration', value='1')
            db.session.add(cfg)
        db.session.commit()
        print('初始化完成：已创建 admin 用户，密码为 admin（请部署后修改）。')


if __name__ == '__main__':
    init()
