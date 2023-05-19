# ORM
A Wireless ORM for PHP 8

Quickstart
```php
$Orm = new Orm(new PDO('dsn','username','password'));

// fetch a user
$user = $orm->Model('users')->fetchByPk(1);

// fetch the users posts
$posts = $user->fetchChildren('posts');
```

Column Values
```php
$model = $orm->Mddel('users');
```