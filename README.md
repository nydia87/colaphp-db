# colaphp-db

数据库工具类

# 使用

## 1、配置部分，单独配置或读写分离配置

- 单库

```php
$config = [
	'driver'    => 'mysql',
	'host'      => '192.168.0.1',
	'database'  => 'test',
    'port'      => 3306,
    'timezone'  => 'Asia/Shanghai',
	'username'  => 'root',
	'password'  => 'root',
	'charset'   => 'utf8',
	'collation' => 'utf8_unicode_ci',
	'prefix'    => '',
];
```

- 主从

```php
$config = [
	'driver'    => 'mysql',
	'master'=>[
        'host'      => '192.168.0.1',
        'database'  => 'test',
        'username'  => 'root',
        'password'  => 'root',
	],
	'slave'=>[
        'host'      => '192.168.0.2',
        'database'  => 'test',
        'username'  => 'root',
        'password'  => 'root',
	],
	'charset'   => 'utf8',
	'collation' => 'utf8_unicode_ci',
	'prefix'    => '',
];
```

## 2、创建对象

```php
	$manager = new ColaPHP\Db\DbManager($config);
	$db = $manager->make();
```

## 3、调试模式

```php
    $logs = $db->debug(function ($me){

        $r2 = $me->insert(
            'insert into member (`username`,`mobile`,`created_at`,`updated_at`) values (?, ?, NOW(), NOW());',
            ['xiaosong', '19812345678']
        );

    });
```

## 4、增删改查

```php
	//查找一条记录
	$db->selectOne("select * from member where mobile = ?",['19812345678']);
	$db->selectOne("select * from member where mobile = :mobile",[':mobile'=>'19812345678']);
	//查找多条记录
	$db->select("select * from member where mobile = ?",['19812345678']);
	//插入
	$db->insert(
		"insert into member (`username`,`mobile`,`created_at`,`updated_at`) values (?, ?, NOW(), NOW());",
		['wang3','19812345678']
	);
	//修改
	$db->update(
		"update member set `created_at` = :created_at where id = :id",
		[ ':created_at' => date('Y-m-d H:i:s'), ':id'=>12 ]
	);
	//删除
	$db->delete("delete from member where id = ?", [16]);
	//exec操作
	$db->exec("delete from member where id = 17");
```

## 5、事务操作

```php
	//常规事务操作
	try{
		$db->beginTransaction();
		$db->update(
			"update member set `created_at` = :created_at where id = :id",
			[ ':created_at' => date('Y-m-d H:i:s'), ':id'=>13 ]
		);
		$db->insert(
			"insert into member (`username`,`mobile`,`created_at`,`updated_at`) values (?, ?, NOW(), NOW());",
			['wang','19812345678']
		);
		$db->commit();
	}catch(Exception $e){
		$db->rollBack();
		var_dump( $e->getMessage() );
	}

	// Closure事务
	$result = $db->transaction(function($me){
		$r1 = $me->update(
			"update member set `created_at` = :created_at where id = :id",
			[ ':created_at' => date('Y-m-d H:i:s'), ':id'=>13 ]
		);
		$r2 = $me->insert(
			"insert into member (`username`,`mobile`,`created_at`,`updated_at`) values (?, ?, NOW(), NOW());",
			['wang4','19812345678']
		);
		return $r1 .'~'. $r2;
	});

```