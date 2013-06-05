<?php
   include "../torm.php";
   include "../models/user.php";
   include "../models/ticket.php";

   class ModelTest extends PHPUnit_Framework_TestCase {
      protected static $con  = null;
      protected static $user = null;

      public static function setUpBeforeClass() {
         $file = realpath(dirname(__FILE__)."/../database/test.sqlite3");
         self::$con  = new PDO("sqlite:$file");
         
         TORM\Connection::setConnection(self::$con,"test");
         TORM\Connection::setDriver("sqlite");
         TORM\Log::enable(false);

         self::$user        = new User();
         self::$user->id    = 1;
         self::$user->name  = "John Doe Jr.";
         self::$user->email = "jr@doe.com";
         self::$user->level = 1;
      }

      public function testConnection() {
         $this->assertNotNull(self::$con);
         $this->assertEquals(self::$con,TORM\Connection::getConnection("test"));
      }      

      public function testFind() {
         $user = User::find(1);
         $this->assertEquals("Eustaquio Rangel",$user->name);
         $this->assertEquals("eustaquiorangel@gmail.com",$user->email);
      }

      public function testNotFound() {
         $user = User::find(10);
         $this->assertNull($user);
      }

      public function testSetAttribute() {
         $user = User::find(1);
         $user->name = "John Doe";
         $this->assertEquals("John Doe",$user->name);
      }

      public function testFirst() {
         $user = User::first();
         $this->assertEquals("Eustaquio Rangel",$user->name);
      }

      public function testFirstWithCondition() {
         $user = User::first(array("email"=>"eustaquiorangel@gmail.com"));
         $this->assertEquals("Eustaquio Rangel",$user->name);
      }

      public function testFirstNotFound() {
         $user = User::first(array("email"=>"yoda@gmail.com"));
         $this->assertNull($user);
      }

      public function testLast() {
         $user = User::last();
         $this->assertEquals("Rangel, Eustaquio",$user->name);
      }

      public function testLastWithCondition() {
         $user = User::last(array("email"=>"taq@bluefish.com.br"));
         $this->assertEquals("Rangel, Eustaquio",$user->name);
      }

      public function testLastNotFound() {
         $user = User::last(array("email"=>"yoda@gmail.com"));
         $this->assertNull($user);
      }

      public function testWhere() {
         $users = User::where(array("name"=>"Eustaquio Rangel"));
         $user  = $users->next();
         $this->assertEquals("Eustaquio Rangel",$user->name);
      }

      public function testWhereWithString() {
         $users = User::where("name='Eustaquio Rangel'");
         $user  = $users->next();
         $this->assertEquals("Eustaquio Rangel",$user->name);
      }

      public function testAll() {
         $users = User::all();
         $user  = $users->next();
         $this->assertEquals("Eustaquio Rangel",$user->name);

         $user  = $users->next();
         $this->assertEquals("Rangel, Eustaquio",$user->name);

         echo "checking all users ...\n";
         foreach(User::all() as $user) {
            echo "user: ".$user->name."\n";
         }
      }

      public function testInsert() {
         $user = new User();
         $user->name    = "John Doe";
         $user->email   = "john@doe.com";
         $user->level   = 1;
         $this->assertTrue($user->isValid());
         $this->assertTrue($user->save());
      }

      public function testUpdate() {
         $user = User::where("email='john@doe.com'")->next();
         $id   = $user->id;
         $user->name = "Doe, John";
         $user->save();

         $this->assertEquals("Doe, John",User::find($id)->name);
         $this->assertTrue($user->isValid());
         $user->save();
      }

      public function testDestroy() {
         $user = User::where("email='john@doe.com'")->next();
         $this->assertTrue($user->destroy());
      }

      public function testCache() {
         $sql = "select * from Users where id=?";
         $this->assertNull(User::getCache($sql));
         User::putCache($sql);
         $this->assertNotNull(User::getCache($sql));
      }

      public function testInvalidPresence() {
         self::$user->name = null;
         $this->assertFalse(self::$user->isValid());
      }

      public function testValidPresence() {
         self::$user->name = "John Doe Jr.";
         $this->assertTrue(self::$user->isValid());
      }

      public function testInvalidFormat() {
         self::$user->email = "yadda@yadda";
         $this->assertFalse(self::$user->isValid());
      }

      public function testValidFormat() {
         self::$user->email = "jr@doe.com";
         $this->assertTrue(self::$user->isValid());
      }

      public function testUniqueness() {
         $old_user = User::find(1);
         $new_user = new User();
         $new_user->name  = $old_user->name;
         $new_user->email = $old_user->email;
         $this->assertFalse($new_user->isValid());
         $this->assertEquals(TORM\Validation::VALIDATION_UNIQUENESS,$new_user->errors["email"][0]);
      }

      public function testNumericality() {
         self::$user->level = "one";
         $this->assertFalse(self::$user->isValid());
      }

      public function testCantSaveInvalidObject() {
         $user = new User();
         $this->assertFalse($user->save());
      }

      public function testLimit() {
         $users = User::where(array("name"=>"Eustaquio Rangel"))->limit(1);
         $this->assertNotNull($users->next());
         $this->assertNull($users->next());
      }

      public function testOrder() {
         $users = User::where("name like '%rangel%'")->order("email desc")->limit(1);
         $user  = $users->next();
         $this->assertNotNull($user);
         $this->assertEquals("taq@bluefish.com.br",$user->email);
      }

      public function testHasMany() {
         $user    = User::find(1);
         $tickets = $user->tickets;
         $this->assertNotNull($tickets);
         echo "\ntickets:\n";
         foreach($tickets as $ticket)
            echo "ticket: ".$ticket->id." ".$ticket->description."\n";
      }

      public function testBelongs() {
         $ticket = Ticket::first();
         $this->assertNotNull($ticket);
         $user = $ticket->user;
         $this->assertNotNull($user);
         echo "user on belongs: ".$user->name."\n";
      }

      public function testCheckEmptySequence() {
         $this->assertEquals(null,User::resolveSequenceName());
      }

      public function testDefaultSequence() {
         $old = TORM\Driver::$primary_key_behaviour;
         TORM\Driver::$primary_key_behaviour = TORM\Driver::PRIMARY_KEY_SEQUENCE;
         $name = User::resolveSequenceName();
         $this->assertEquals("users_sequence",User::resolveSequenceName());
         TORM\Driver::$primary_key_behaviour = $old;
      }

      public function testNamedSequence() {
         $old = TORM\Driver::$primary_key_behaviour;
         TORM\Driver::$primary_key_behaviour = TORM\Driver::PRIMARY_KEY_SEQUENCE;
         $test = "yadda_sequence";
         User::setSequenceName($test);
         $name = User::resolveSequenceName();
         $this->assertEquals($test,User::resolveSequenceName());
         TORM\Driver::$primary_key_behaviour = $old;
      }
   }
?>