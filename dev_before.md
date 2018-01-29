# 概要

デザインパターンはすでにご存知の通り様々な種類がありますが
今回のプロジェクトを準備するところで
今後どう行くべきかについてデザインパターンを元に
いくつかのパターンを
簡単な例で説明したいと思います。

デザインパターンの原則の中で
`プログラムはインターフェイスに対して行う（実装に対して行わない）`
`ISP : Interface Segregation Principle`

ことを覚えてみると
インターフェイスに合わせてコーディングをすればシステムで起きうる様々な変化を乗り切ることができる。という意味になると思います。

システムの拡張についても
最初の原則"変わる部分をカプセル化せよ（変わるものを変わらないものから分離する）"を記憶するこどで
ある程度のシステム拡張には対応出来ると思いますね。

この拡張という意味の通り
今回のサービスはいろんなアプリにSDKとして提供するかもしれないし
別のアプリとして一部の機能だけ使いたい場合もあると思いますね。

なので今回のプロジェクトからは
どんな拡張でも簡単に対応出来るように
設計したいということで
デザインパターンの観点では何が良いかを考えてみます。


## Factory Pattern

オブジェクト生成を処理するクラスをファクトリーと呼びます。
普通、JAVAでnewはコンクリートオブジェクトを意味しますね。
これはnewを使用すれば具象クラス(コンクリートクラス, Concrete Class)のインスタンスを作るという意味です。

最初OOPを勉強する際
インターフェイスではなく特定の具現に依存してはよくないと覚えました。
(後でコードの修正しづらくなる可能性が高まって、柔軟性も落ちるので)

ということで
簡単なFactory Patternの例で
コーヒーを注文するクラスを作ってみると。

```
<?php

$coffee = new CoffeeStore;
$coffee->orderCoffee('ice');
$coffee->orderCoffee('latte');
$coffee->orderCoffee('mocha');

class CoffeeStore
{
    public function orderCoffee( $type ) {
        $coffee = null;
        switch ( $type ) {
            case 'ice':
                $coffee = new IceCoffee();
                break;
            case 'latte':
                $coffee = new LatteCoffee();
                break;
            case 'mocha':
                $coffee = new MochaCoffee();
                break;
        }
         
        $coffee->prepare();
        $coffee->drip();
        $coffee->ready();
         
        return $coffee;
    }
}


class IceCoffee
{
    public function prepare()
    {
        echo 'アイスコーヒーを準備中' . PHP_EOL;
    }

    public function drip()
    {
        echo 'アイスコーヒーをドリップ中' . PHP_EOL;
    }

    public function ready()
    {
        echo 'アイスコーヒーを完了' . PHP_EOL;
    }
}

class LatteCoffee
{
    public function prepare()
    {
        echo 'ラテコーヒーを準備中' . PHP_EOL;
    }

    public function drip()
    {
        echo 'ラテコーヒーをドリップ中' . PHP_EOL;
    }

    public function ready()
    {
        echo 'ラテコーヒーを完了' . PHP_EOL;
    }
}

class MochaCoffee
{
    public function prepare()
    {
        echo 'モカコーヒーを準備中' . PHP_EOL;
    }

    public function drip()
    {
        echo 'モカコーヒーをドリップ中' . PHP_EOL;
    }

    public function ready()
    {
        echo 'モカコーヒーを完了' . PHP_EOL;
    }
}
```

こうなると思いますね。
多分これは普通にデザインパターンとか考えず
今まで様々なプロジェクトでもやってたと思います。

ところで
将来的に新しい種類のコーヒーを追加したいときや
ある種類のコーヒーをメニューから消したいとき
switchの中身を編集しますよね。

しかし、このやり方ではOCPとSRPに違反してしまいます。

```
http://tdak.hateblo.jp/entry/20130703/1372842149

単一責任の原則 (SRP : Single Responsibility Principle)
オープン・クローズドの原則 (OCP : Open-Closed Principle)
リスコフの置換原則 (LSP : Liskov Substitution Principle)
依存性逆転の原則 (DIP : Dependency Inversion Principle)
インタフェース分離の原則 (ISP : Interface Segregation Principle)
```

そのため、これから先コードが変わりそうな「コーヒーを作る」部分は、
orderCoffeeから切り離して
他のクラスに依頼するのが望ましく
その依頼先のクラスが
「Factory（CoffeeFactory）」クラスとなります。

「Factory」クラスの仕事はもっぱら「Coffee」を生産することです。
この「工場」は自社で持っていないため、いつでも別の違う工場に簡単に乗り換えることができ、全く違うコーヒーメニューを簡単に作ることができるというメリットもあります。

例として下記を見ると

```
<?php

abstract class Coffee {
    protected $name;
    protected $toppings = array();
 
    public function get_name() {
        return $this->name;
    }
 
    public function prepare() {
        echo $this->name . '準備中' . PHP_EOL;
    }
 
    public function drip() {
        echo $this->name . 'ドリップ中' . PHP_EOL;
    }

    public function ready() {
        echo $this->name . '完了' . PHP_EOL;
    }
 
    public function describe() {
        echo '---' . $this->name . '---' . PHP_EOL;
        echo '追加トッピング : ' . PHP_EOL;
        foreach ( $this->toppings as $topping ) {
            echo '　--' . $topping . PHP_EOL;
        }
        echo PHP_EOL . PHP_EOL;
    }
}
 
class IceCoffee extends Coffee {
 
    function __construct() {
        $this->name       = 'アイスコーヒー';
        $this->toppings[] = '無し';
    }
}
 
class LatteCoffee extends Coffee {
 
    function __construct() {
        $this->name       = 'ラテ';
        $this->toppings[] = 'ミルク';
    }
}
 
class MochaCoffee extends Coffee {
 
    function __construct() {
        $this->name       = 'モカコーヒー';
        $this->toppings[] = 'モカ';
        $this->toppings[] = 'シロップ';
        $this->toppings[] = 'ミルク';
    }
}
 
// Factory Class
class CoffeeFactory {
    /**
     * The Factory-Method
     *
     * @param string $type
     *
     * @return Coffee
     */
    public function createCoffee( $type ) {
        $coffee = null;
        switch ( $type ) {
            case 'ice':
                $coffee = new IceCoffee();
                break;
            case 'latte':
                $coffee = new LatteCoffee();
                break;
            case 'mocha':
                $coffee = new MochaCoffee();
                break;
        }
 
        return $coffee;
    }
}
 
class CoffeeStore {
    private $factory;
 
    function __construct( CoffeeFactory $factory ) {
        $this->factory = $factory;
    }
 
    public function orderCoffee( $type ) {
        $coffee = $this->factory->createCoffee( $type );
        $coffee->prepare();
        $coffee->drip();
        $coffee->ready();
 
        return $coffee;
    }
}
 
 
// Order Coffee from Store
$coffeeStore  = new CoffeeStore( new CoffeeFactory() );
 
$iceCoffee = $coffeeStore->orderCoffee( 'ice' );
$iceCoffee->describe();
 
$latteCoffee = $coffeeStore->orderCoffee( 'latte' );
$latteCoffee->describe();


```

Coffeeオブジェクトを生成する手順を
Factoryを使ってクライアント（CoffeeStore）から分離することで
クライアントはコーヒーの種類が何かを心配する必要がなくなり
自分のやるべきことに集中することができるようになりました。


これを普通Factory Method Patternと言いますね。

ところでまた、
チェーン店ができた場合について考えてみましょう。

地域によってはメニューが違ったり材料が違ったりしますよね。

そうなると、「アイスコーヒーならこのストアが美味しい」という風に
条件分岐がたくさん増え、読みづらいコードになってしまいます。

そこで、複数のストアクラスを作ってストアごとに違う工場を使うことで、
例えば同じ名前のコーヒーでも豆が違うようにすることができます。

```
<?php

// 工場(CoffeeFactory)が作っている商品(Coffee)
abstract class Coffee {
    protected $name;
    protected $item;
    protected $toppings = array();
 
 
    public function prepare() {
        echo $this->name . '準備中' . PHP_EOL;
    }
 
    public function drip() {
        echo $this->name . 'ドリップ中' . PHP_EOL;
    }

    public function ready() {
        echo $this->name . '完了' . PHP_EOL;
    }

    public function db() {
        // 注文処理
        echo $this->name . '情報をDBに保存(itemid : ' . $this->item . ')' . PHP_EOL;
    }
 
    public function describe() {
        echo '---' . $this->name . '---' . PHP_EOL;
        echo '追加トッピング : ' . PHP_EOL;
        foreach ( $this->toppings as $topping ) {
            echo '　--' . $topping . PHP_EOL;
        }
        echo PHP_EOL . PHP_EOL;
    }
}

// コーヒー工場
interface CoffeeFactory {
    /**
     * @param string $type
     *
     * @return Coffee
     */
    public function createCoffee( $type );
}

// コーヒーストア
abstract class CoffeeStore {
    /** @type  CoffeeFactory $factory */
    protected $factory;
 
    /**
     * 全チェーン店に共通する製法
     *
     * @param string $type
     *
     * @return Coffee
     */
    public function orderCoffee( $type ) {
        $coffee = $this->factory->createCoffee( $type );
        $coffee->prepare();
        $coffee->drip();
        $coffee->ready();
        $coffee->db();
 
        return $coffee;
    }
}

class Local_IceCoffee extends Coffee {
 
    function __construct() {
        $this->name       = 'LOCALアイスコーヒー';
        $this->item       = 1;
        $this->toppings[] = '無し';
    }
 
}
 
class Local_LatteCoffee extends Coffee {
 
    function __construct() {
        $this->name       = 'LOCALラテ';
        $this->item       = 2;
        $this->toppings[] = 'ミルク';
    }
 
}
 
class Local_MochaCoffee extends Coffee {
 
    function __construct() {
        $this->name       = 'LOCALモカコーヒー';
        $this->item       = 3;
        $this->toppings[] = 'モカ';
        $this->toppings[] = 'シロップ';
        $this->toppings[] = 'ミルク';
    }
 
}
 
 
class Gorilla_IceCoffee extends Coffee {
 
    function __construct() {
        $this->name       = 'Gorillaアイスコーヒー';
        $this->item       = 4;
        $this->toppings[] = '氷';
        $this->toppings[] = 'はちみつ';
        
    }
}
 
class Gorilla_LatteCoffee extends Coffee {
 
    function __construct() {
        $this->name       = 'Gorillaラテ';
        $this->item       = 5;
        $this->toppings[] = 'ミルク';

    }
}
 
class Gorilla_MochaCoffee extends Coffee {
 
    function __construct() {
        $this->name       = 'Gorillaモカコーヒー';
        $this->item       = 6;
        $this->toppings[] = 'モカいっぱい';
        $this->toppings[] = 'はちみつ';
    }
}
 
class Local_CoffeeFactory implements CoffeeFactory {
    public function createCoffee( $type ) {
        $coffee = null;
        switch ( $type ) {
            case 'ice':
                $coffee = new Local_IceCoffee();
                break;
            case 'latte':
                $coffee = new Local_LatteCoffee();
                break;
            case 'mocha':
                $coffee = new Local_MochaCoffee();
                break;
        }
 
        return $coffee;
    }
}
 
class Gorilla_CoffeeFactory implements CoffeeFactory {
    public function createCoffee( $type ) {
        $coffee = null;
        switch ( $type ) {
            case 'ice':
                $coffee = new Gorilla_IceCoffee();
                break;
            case 'latte':
                $coffee = new Gorilla_LatteCoffee();
                break;
            case 'mocha':
                $coffee = new Gorilla_MochaCoffee();
                break;
        }
 
        return $coffee;
    }
}
 
 
class Local_CoffeeStore extends CoffeeStore {
 
    function __construct() {
        $this->factory = new Local_CoffeeFactory();
    }
}
 
class Gorilla_CoffeeStore extends CoffeeStore {
 
    function __construct() {
        $this->factory = new Gorilla_CoffeeFactory();
    }
}
 
$localCoffeeStore = new Local_CoffeeStore();
$iceCoffee   = $localCoffeeStore->orderCoffee( 'ice' );
$iceCoffee->describe();
 
$mochaCoffee = $localCoffeeStore->orderCoffee( 'mocha' );
$mochaCoffee->describe();
 
$gorillaCoffeeStore = new Gorilla_CoffeeStore();
$iceCoffee        = $gorillaCoffeeStore->orderCoffee( 'ice' );
$iceCoffee->describe();
 
$mochaCoffee = $gorillaCoffeeStore->orderCoffee( 'mocha' );
$mochaCoffee->describe();

```

これを普通に
Abstract Factory Patternと言います。

まあ、あえてパターンの種類が分けてるのは
GoF (Gang of Four; 四人組) と呼ばれる4人の勝手かもしれないですが
要は

### ファクトリーメソッドパターン (Factory Method Pattern)
- クライアントのコードとインスタンス作るコンクリートクラスを分離しないといけない時
- どんなコンクリートクラスが必要となるかわからない場合

### 抽象ファクトリーパターン(Abstract Factory Pattern)
- クライアントで互いに連関した一連の製品を作りたいとき

ですので
o:derの場合はAFPの方でStrategy Patternを混ぜた設計が良いかもですね。
と思いました。


## 結論

いろいろ複雑に見えると思いますが
簡単に整理すると。

```
どんなパターンを使っても、
結局オブジェクト生成をカプセル化してアプリケーションの結合を緩くして、
特定の実装に依存性をチョイ減らすように作ることができるので
用途に応じて適切なパターンを使用すればよい。
```

ですね。

多分一番いいことは依存性をゆるくすること。ですかね。

次回、Strategy Pattern含めて
プロジェクトの仕様がある程度決まると
全体機能をまとめたあと
設計に入り、
出た内容を改めて共有したいと思います。

## 参照

https://liginc.co.jp/web/programming/php/136131
https://liginc.co.jp/web/programming/php/149051
http://designpatternsphp.readthedocs.io/en/latest/Creational/FactoryMethod/README.html
