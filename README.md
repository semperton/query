<div align="center">
<a href="https://github.com/semperton">
<img src="https://avatars0.githubusercontent.com/u/76976189?s=140" alt="Semperton">
</a>
<h1>Semperton Query</h1>
<p>A small and standalone SQL query builder.</p>
//
</div>
<hr>

## Installation

Just use Composer:

```
composer require semperton/query
```
Query requires PHP 7.2+

## Intro

This is a SQL query builder only.
All Instances of ```ExpressionInterface``` provide a ```compile``` method which takes a parameter array by reference and returns a parameter substituted SQL string that can be used with ```PDO``` for example.
```PHP
$queryFactory = new QueryFactory();
$userSelect = $factory->select('user')->limit(5);

$params = [];
$sql = $userSelect->compile($params);

$pdo = new PDO('dsn');
$stm = $pod->prepare($sql);
$stm->execute($params);
$users = $stm->fetchAll();

// ...
```

## Usage
Use your editor's autocomplete features for now ;)