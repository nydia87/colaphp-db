# colaphp-db
# 配置部分，单独配置或读写分离配置
```php 
$config = [
	'driver'    => 'mysql',
	'host'      => '192.168.0.1',
	'database'  => 'test',
	'username'  => 'root',
	'password'  => 'root',
	'charset'   => 'utf8',
	'collation' => 'utf8_unicode_ci',
	'prefix'    => '',
];

$config2 = [
	'driver'    => 'mysql',
	'write'=>[
        'host'      => '192.168.0.1',
        'database'  => 'test',
        'username'  => 'root',
        'password'  => 'root',
	],
	'read'=>[
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

# 创建对象

```php
    // 直接调用
	$dbFactory = new  \Colaphp\Db\Connectors\ConnectionFactory();
	$db = $dbFactory->make($config,'MYSQL');
    // 助手函数调用
	$db = getPDO('default.mysql',$config);
```

# 调试日志
```php
	//开启日志
	$db->enableQueryLog();
	//获取日志
	$db->getQueryLog();
```

# 增删改查

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
	$db->unprepared("delete from member where id = 17");
```

# 事务操作

```php
	//常规事务操作
	try{
		$db->beginTransaction();
		//事务数
		var_dump($db->transactionLevel());
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
