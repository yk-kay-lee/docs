# 依存性の注入と制御の反転

## コードはシンプルな方がいいのではないですか？

もちろん。です。

ふむ、
軽くサンプルコードを見ますと
マイレージの取得、アップデートなどくらいなのにファイル数も多いし何故？
と思うじゃないですか

もちろん下記のようにシンプルにも出来ます。
例として今回のマイレージ取得の例で。

```php
<?php
/**
 * Class MypageController
 *
 * PHP version 7
 *
 * @category  Mypage
 * @package   App\Http\Controllers
 */
 
use App\Model\Mileage;

class MypageController extends Controller
{
    /**
     * Mypage List
     *
     *
     * @return view
     */
    public function index() {
        $account_code = 'debug_test';

        // mileage get
        $mileage = new Mileage;
        $userMileage = $mileage->getAllByAccountCode('debug_test');
        
        return view('mypage')->with('data', $userMileage);

    }
}
```

```php
<?php
namespace App\Model;

use App\Services\Database\Eloquent\DynamicModel;

class Mileage extends DynamicModel
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'mileage';

    /**
     * @param $id
     *
     * @return mixed
     */
    public function getAllByAccountCode($account_code)
    {
        return $this->query()
            ->where('account_code', $account_code)
            ->orderBy($this->primaryKey, 'ASC')
            ->get();
    }
}
```

もっと簡単にすると
上のcontrollerで
今回のプロジェクトで用意したLarabel用のDB Wrapperを使う。

```php
// 追加して
use DB;

// Modelなんかも要らなくて
// use App\Model\Mileage;
...
...

public function index() {
    $account_code = 'debug_test';

    // mileage get
    $userMileage = DB::table('mileage')
        ->where('account_code', $account_code)
        ->get();
        
    return view('mypage')->with('data', $userMileage);
}


```
”簡単ですよね。”
と思うじゃないですか。

私もシンプルなコードで読みやすいコードが一番だと思います。
ですが
一旦
上の参照URLにまた戻りまして
実際はそうした理由があります。
下記の例で説明したいと思います。

## 大規模サービスでは柔軟性が大事
続いて、その理由ですが
サービスが大規模になることを想定すると
1. パフォーマンス
2. エンジニア別作ったクラスの依存性を減らしてコードの再使用生を上げる
3. 品質(単体テスト、QAなど)
4. 様々な要請に対する柔軟な対応が必要になります。
他にも考えないといけないことが多いと思いますが
こういうことを考えた実装の例としたものであります。

まず、下記を見てください。

依存性の注入(DI:Dependency injection)と制御の反転(IoC;Inversion of Control)はモジュール間の依存性を減らして、
ソース修正も無しで、
ランタイムでより柔軟なソフトウェアを作ることができる開発のパターンです。
（誰でも知っている話ですみません）
そしてRepository Patternは
- テストがしやすくなる
- DBエンジンの変更に対応しやすくなる
- データ操作のロジックが1箇所にまとまり、管理しやすくなる

とりあえず、
依存性の注入は、オブジェクト指向言語で提供するインタフェース(Interface)を活用しつつ、
実装されます。

### 例1
ユーザーの情報を保存して読んでくるUserRepositoryクラスがあり、
その中にはリポジトリからユーザーの情報を処理する
$repositoryという変数があります。

サービス初期、ユーザーが少なくてユーザーの情報はコンマと区分されたCSVファイルで読むことにしました。 
もちろん、今後の拡張性のためにRepositoryInterfaceというインタフェースを作ってCSVRepositoryはこれを具現したクラスです。

```php
<?php
 
class UserRepository implements RepositoryInterface {
    private $repository;
 
    public function __construct() {
        $this->repository = new CSVRepository;
    }
}
```

サービスコードは次のようにインタフェースを実装したクラスのインスタンスを生成した後で
使用するように作成します。

```php
// repository 生成及び使用
RepositoryInterface $repos = new UserRepository();
$repos->store();
```

### 例2

もうサービスはユーザーが多くてユーザー情報をMySQL DBMSで処理することにし、
関連ロジックを実装しました。 そしてUserRepositoryを次のように修正します。

```php
<?php
 
class MySQLUserRepository implements RepositoryInterface {
    private $repository;
 
    public function __construct() {
        $this->repository = new MySQLRepository;
    }
}
```

```php
// repository 生成及び使用
RepositoryInterface $repos = new MySQLUserRepository();
$repos->store();
```

### 例３

サービスはさらにユーザーが多くなって性能改善に向けてリポジトリをredis key/value storeに変更することにしました。
インタフェースをうまく設計し、これを具現化したとすると、
上記のような状況でRedisUserRepositoryクラスを実装して
既存サービスのインスタンスの生成、ソースを修正して対処することができます。

```php
<?php
 
class RedisUserRepository implements RepositoryInterface {
    private $repository;
 
    public function __construct() {
        $this->repository = new RedisRepository;
    }
}
```

しかし、外部でこの製品を使用したいという会社が2社現れたと仮定しましょー。
それぞれDBMSはMS-SQLとPostgreSQLを使用しているようです。

ユーザーのリポジトリをインターフェースで作ったのですが、実際サービスコードでは、インタフェースを実装したコードが含まれなければいけない、
顧客が使用するDBによって製品を分ける場合、
バージョン管理が難しくなり新しいDBが追加された場合、インスタンスを生成するコードも引き続き修正されなければなりません。

まぁ、しようがないかと思って
とりあえずMSSQL一個作成してみる。

```php
 <?php
 
class MSSQLUserRepository implements RepositoryInterface {
    private $repository;
 
    public function __construct() {
        $this->repository = new MSSQLRepository;
    }
}
```
```php
// repository 生成及び使用
RepositoryInterface $repos = new MSSQLUserRepository();
$repos->store();
```
あああ、いつまで？
どんなにシンプルなコードで開発しても
こうクラスが増えたりすると意味ないですね。

### 結論

すみません、話長くなってしまって。

簡単に言うと
依存性の注入や制御の反転を使用すれば、
インタフェースを使用するソースの修正も無しで
ランタイムで依存性を決定することができます。
下記みたいに。

```php
<?php
 
class UserRepository implements RepositoryInterface {
    private $repository;
 
    public function __construct(RepositoryInterface $repository) {
        $this->repository = $repository;
    }
}
```

もう依存性制御処理ロジックで設定ファイルやオプションでRepositoryInterfaceを実装したインタフェースを生成してUserRepositoryに注入すると、他のリポジトリを使用するところが現れてもソースの見直しをせず、設定だけで処理することができます。


上と同様の手法を依存性の注入と言います。
外部で依存性が注入されるため、このような制御を制御の反転と呼びます。 
依存性の注入は一般的なサービスやアプリケーションでは考慮しなくても良いケースが多いですが、
外部環境で動作しなければならない製品(ソリューション、フレームワーク)は、
導入を考慮してみるべきかなと思います。

依存性を注入する方法は上記のコンストラクタを利用する方法とセッター(setter)を利用する方法がありますが、
Laravelは前者を使用しています。

Laravelは多くの部分をランタイムに依存性を注入しており、
注入する依存性インターフェイスは、タイプのヒントを通じて決定しています。
また、依存性インターフェイスを具体的に具現した
プロバイダは普通、
設定ファイルで読んで設定ファイルの内容だけを変更すれば、アプリケーションを修正せず、柔軟にパッケージを使用することができます。

```php
// config/cache.php : laravel cache config file
// 'default' => env('CACHE_DRIVER', 'memcached'),
// 'default' => env('CACHE_DRIVER', 'apc'),
// 'default' => env('CACHE_DRIVER', 'file'),
// 'default' => env('CACHE_DRIVER', 'database'),
// 'default' => env('CACHE_DRIVER', 'tokyotycoon'),
'default' => env('CACHE_DRIVER', 'redis'),

```


では最後に
参照URLを改めて見てください。
interface, DI, Repository Patternを利用した開発は
モダーンなデザインパターンという話は除くても
大規模ウェブアプリケーション、サーバーアプリケーション、APIの開発で
柔軟性いかして開発の生産性を高めることが出来ます。

以上。