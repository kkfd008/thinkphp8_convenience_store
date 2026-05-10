# 便利店零售管理系统

基于 PHP8 + ThinkPHP 8 + SQLite3 + Layui 2.9.10 构建

## 部署步骤

1. **环境要求**：PHP >= 8.0，需开启 `pdo_sqlite` 扩展

2. **安装依赖**：
```bash
composer install
```

3. **初始化数据库**：
```bash
php -r "$db = new PDO('sqlite:database/shop.db'); $db->exec(file_get_contents('database/schema.sql'));"
```

4. **配置 `.env`** 中数据库路径（默认已配置）：
```
DB_DRIVER = sqlite
DB_DATABASE = d:/dev/project/newbld/database/shop.db
```

5. **启动开发服务器**：
```bash
php think run
```

6. **访问** `http://localhost:8000`，使用默认账号 `admin / admin123` 登录

## 默认角色

- 超级管理员：admin / admin123（拥有全部权限）
