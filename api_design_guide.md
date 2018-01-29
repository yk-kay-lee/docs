# REST APIデザインガイド

REST APIデザインは
REST思想にあわせてまともにデザイン(CRUDをHTTP methodに合わせた)にすることも難しいし
URI Conventionなどやセキュリティ、バージョン管理など考慮する事項が多い。
と言うことで
今回はREST APIデザインについてのガイドを紹介したいと思います。

## REST URIはシンプルで直観的なものが良い

REST APIをURIだけ見ても直感的に理解できるように。
URLを長く作るより最大2depth程度で簡単に作ることが理解しやすい。

- /dogs
- /dogs/1234

## URIにリソース名は動詞よりは名詞を使用する。

REST APIはリソースに対して行動を定義する形を使用する。 
たとえば

- POST/dogs

は/dogsというリソースを生成するという意味で
URLはHTTP MethodによってCRUD(CREATE, READ, UPDATE, DELETE)の対象となる個体(名詞)でなければならない。

悪い例を見ると

- HTTP Post:/getDogs
- HTTP Post:/setDogsOwner

上の例は行為をHTTP Postで定義しなく
get/setなどの行為をURLにつけた場合で良くない例ですね。

これよりは

- HTTP Get:/dogs
- HTTP Post:/dogs/{puppy}/owner/{terry}

を使った方が良い。
そしてなるべくなら意味上単数形名詞(/dog)よりは複数形名詞(/dogs)を使用することが意味上の表現するのがもっといい。

### 一般的に勧告されるデザインは以下の通りです。

|リソース|POST|GET|PUT|DELETE|
|:-----|:-----|:-----|:-----|:-----|
||create|read|update|delete|
|/dogs|新しいdogs登録|dogsリストをゲット|bulkで多数の情報をアップデート|全てのdogsを削除|
|/dogs/maru|エラー|maruというdogs情報をゲット|maruというdogs情報をアップデート|maruというdogs情報を削除|

## リソース間の関係を表現する方法

RESTリソース間には互いに連関関係があり得る。
例えば、ユーザが所有しているデバイスリストやユーザーが持っている子犬たちなどが例にすると
デバイスまたは子犬などと同じそれぞれのリソース間の関係を表現する方法はいくつかありまして

### Option 1.サブリソースと表現する方法

例えば、ユーザが持っている携帯電話デバイスリストを表現してみると

/"リソース名"/"リソースid"/"関係がある他のリソース名"形 
HTTP Get:/users/{userid}/devices
例)/users/hoge/devices
のように/hogeという使用者が持っているデバイスリストをリターンする方法があり

#### Option 2.サブリソースに関係を明示する方法

もし関係が複雑なら関係名を明示的に表現する方法がある。
例えば、ユーザが"好きな"デバイスリストを表現してみると

HTTP Get:/users/{userid}/likes/devices
例)/users/hoge/likes/devices
はhogeというユーザーの好きなデバイスリストをリターンする方式。

Option 1、2
どのような形を使用しても問題はありませんが
Option 1の場合
一般的に所有"has"の関係を黙示的に表現する時に良くて
Option 2の場合には
関係が曖昧など具体的な表現が必要なときに使用する。

## エラー処理

エラー処理の基本はHTTP Response Codeを使用した後
Response bodyにerror detailを表示する形式がいい。

代表的なAPIサービスがどのようなHTTP Response Codeを使用するかを見ると次のようになる。

Googleのgdataサービスの場合10個
Netflixの場合9個
Diggの場合8個のResponse Codeを使用する。
(情報の出所:https://pages.apigee.com/rs/apigee/images/api-design-ebook-2012-03.pdf)

### Google GData

200 201 304 400 401 403 404 409 410 500


### Netflix

200 201 304 400 401 403 404 412 500

### Digg

200 400 401 403 404 410 500 503

いくつかのresponse codeを使用すれば明示的ではありますが
コード管理が難しいため下記のようにいくつかのresponse codeのみを使用することをオススメ。

- 200 成功
- 400 Bad Request-field validationの失敗
- 401 Unauthorized-API認証、認可失敗
- 404 Not found-該当リソースがない
- 500 Internal Server Error-サーバーエラー

追加でHTTP response codeの使用が必要ならば
http response code定義
http://en.wikipedia.org/wiki/Http_error_codes
文書を参考とすること。

エラーにはエラー内容についた詳細内容を
http bodyに定義して詳細なエラーの原因を伝えることがデバッグに有利です。

TwilloのError Messageの形式の場合

- HTTP Status Code:401

```
{"status":"401"、"message":"Authenticate"、"code":200003、"more info":"http://www.twillo.com/docs/errors/20003"}
```

のように表現して
エラーコード番号と該当エラーコード番号に対するError dictionary linkを提供する。

これはAPIだけでなくよく定義されたソフトウェア製品の場合は
別途のError番号に対するDictionaryを提供していて
OracleのWebLogicの場合にも

http://docs.oracle.com/cd/E24329_01/doc.1211/e26117/chapter_bea_messages.htm#sthref7

のようにError番号これに対する詳しい説明と解決方法などを説明する。
これは開発者やTrouble Shootingする人に多くの情報を提供して
もっとデバッグしやすくする。(なるべくならError Code番号を提供するのがいい。)

そしてエラー発生時に選択的にエラーに関するスタック情報を含めることができる。

エラーメッセージでError Stack情報を出力することは非常に危険なことです。
内部的なコード構造とフレームウォーク構造を外部に露出することにより
ハッカーにハッキングできる情報を提供するためで
一般的なサービスの構造ではエラースタック情報をAPIエラーメッセージに含めないのが良い。

しかしながら、
内部開発中かデバッグ時には非常に有用ですので
APIサービスを開発時サーバのモードをproductionとdevモードで分離して
オプションによってdevモードなどで起動時には
REST APIのエラー応答メッセージにエラースタック情報を含めてリターンするようにすれば
デバッグに非常に有用に使用することができる。

## APIバージョン管理

API定義で重要なものの一つはバージョン管理です。
既に配布されたAPIの場合には継続してサービスを提供しつつ
新たな機能が入った新しいAPIを配布する時は下位互換性を保障しサービスを提供しなければならないため
同じAPIでもバージョンによって異なる機能を提供するようにすることが必要ですね。

APIのバージョンを定義する方法には色々ありまして

- Facebook?v=2.0
- salesforce.com/services/data/v20.0/sobjects/Account

個人的には

- {servicename}/{version}/{REST URL}
- example)api.server.com/account/v2.0/groups
- example)account.server.com/v2.0/groups

形で定義することがオススメですが
これに正解はないためサービスに応じて適切に選択すれば良いかなと。

## ページング

大きなサイズのリストResponseを処理するためには
ページングの処理とpartial response処理が必要。

リターンするリスト内容が1,000,000件なのに
これを一つのHTTP Responseで処理することは
サーバの性能、ネットワーク費用も問題ですが何より非現実的ですね。
それでページングを考慮することが重要。

ページングを処理するためには様々なデザインがあって

例えば、100番目のレコードから125番目のレコードまで受けるAPIを定義すると

- Facebook APIスタイル:/record?offset=100&limit=25

- Twitter APIスタイル:/record?page=5&rpp=25(RPPはRecord per pageでページングあたりレコード数RPP=25ならページ5は100~125のレコードになる。)
- LikedIn APIスタイル:/record?start=50&count=25

実装観点から見るとFacebook APIがもっと直観的であるため
Facebookスタイルを使用することがオススメ。

record?offset=100&limit=25
→
100番目のレコードから25個のレコードを出力する。


## Partial Response処理

リソースに対するResponseメッセージについて
あえてすべてのフィールドを含める必要がないケースがある。

例えば
Facebook FEEDの場合には
使用者ID、名前、書き込み内容、日付、いいですよカウント、コメント、使用者写真などなど
様々な情報を持っていて
APIをRequestするClientの用途によって選別的にいくつかのフィールドのみ必要な場合がある。
フィールドを制限することは全体Response量を減らしてネットワーク帯域幅(特にモバイルで)節約ができますしResponseメッセージを簡素化してパーシングなどを簡略化することができる。

それでいくつかのよくデザインされた
REST APIの場合このようなPartial Response機能を提供していて
主要サービスを比較してみると。

- Linked in:/people:(id、first-name、last-name、industry)
- Facebook:/hoge/friends?fields=id、name
- Google:?fields=title、media:group(media:thumnail)

Linked inスタイルの場合可読性は高いですが `:()` で区別するため
HTTPフレームウォークにパーシングすることが難しい。
全体を一つのURLと認識して `:(` 部分を別途のParameterで区別しないからです。

FacebookとGoogleは似ていた方法で
特にGoogleのスタイルはもっと面白と思うのが
group(media:thumnail)のようにJSONのSub-Objectの概念を支援する。

Partial ResponseはFacebookスタイルが実装することが簡単なので
個人的にはFacebookスタイルのpartial responseを使用することをオススメ。


## 検索(global検索とlocal検索)

検索は一般的にHTTP GETでQuery Stringに検索条件を定義するのが一般的でありますが
この場合検索条件が他のQuery Stringと混ざることがある。

例えば、name=hogeでregion=fugaのユーザーを検索するとき
Query Stringのみ使用することにすると下記のように表現することができる。

- `/users?name=hoge&region=fuga`

ところが、ここにページング処理を追加すると

- `/users?name=hoge&region=fuga&offset=20&limit=10`

ページング処理に定義されたoffsetとlimitが検索条件なのかページング条件なのか
わからない。
ですので条件は一つのQuery Stringに定義することがよくて

- `/user?q=name%3Dhoge、region%3Dfuga&offset=20&limit=10`

こんなふうに検索条件をURLEncodeで書いて"q=name%3Dhoge、region%3D=fuga"のように(実際にはq=name=hoge、region=fuga)表現して
Deleminatorを `,` などを使うようになれば
検索条件は他のQuery Stringと分離される。

もちろん、この検索条件はサーバーによってトークン単位でパーシングしなければならない。

そして検索の範囲について考慮する必要があって
Globalでの検索は全体リソースに対する検索を
リソースに対する検索は特定のリソースに対する検索を定義する。

例えば
システムにuser、dogs、carsと同じリソースが定義されているとき
id='terry'なリソースに対するGlobalでの検索は

- `/search?q=id%3Dhoge`

のように定義。 /searchと同じGlobal検索URIを使用することです。

逆に特定のリソースの中だけの検索は

`/users?q=id%3Dhoge`
のようにリソース名にクエリー条件を付けるように表現することが可能。


## HATEOSを利用したリンク処理

HATEOSはHypermedia as the engine of application stateで、
Hypermediaの特徴を利用してHTTP Responseに次のActionや関係されるリソースに対するHTTP Linkを一緒にリターンすること。

例えば、上のページング処理の場合リターンの際前後のページのリンクを提供したり

```
{  
   [  
      {  
         "id":1,
         "name":"hoge"
      },
      {  
         "id":2,
         "name":"fuga"
      }
   ],
   "links":[  
      {  
         "rel":"prev_page",
         "href":"https://id.roover.jp/users?offset=6&limit=5"
      },
      {  
         "rel":"next_page",
         "href":"https://id.roover.jp/users?offset=11&limit=5"
      }
   ]
}


```

関連されたリソースについた詳細なリンクを表示することなどで。

```
{  
   "id":2,
   "links":[  
      {  
         "rel":"friends",
         "href":"http://id.roover.jp/users/2/friends"
      }
   ]
}

```

HATEOASをAPIに適用すると
Self-Descriptive特性が増大してAPIについた可読性が増加する長所を持っているが
Responseメッセージが他のリソースURIに対する依存性を持つため
実装がやや厳しいという短所がある。


## 単一APIエンドポイントの活用

APIサーバーが物理的に分離された複数のサーバーで動作しているとき
user.apiserver.com、car.apiserver.com

のようにAPIサービスごとにURLが分離されていれば結構不便ですね。
毎度違うサーバーに接続しなければならず中間にFirewallでもあればいちいちFirewallを解除しなければならない。

APIサービスは、物理的にサーバが分離されていても
単一URLを使用するのが良くて
方法はHAProxyやnginxのようなreverse proxyを使用する方法がある。
HAProxyを前に立ててapi.apiserver.comという単一URLを構築した上で

HAProxy設定で

api.apiserver.com/userはuser.apiserver.comでルーティングさせて
api.apiserver.com/carはcar.apiserver.comでルーティングするように実装すればよい。

このようにすると今後裏側のAPIサーバーが拡張になっても
APIを使用するクライアントの立場では単一エンドポイントを見ればよくて
管理の観点からも
単一エンドポイントを通じて、負荷分散およびログを通じたAudit(監査)などができるため
便利です。
APIに対するルーティングをreverse proxyを利用してもっと柔軟な運用が可能です。


## RESTの問題点

このように多くのメリットがあるRESTは万能なのか?
RESTもいくつかのホールと短所を持っている。


### JSON+HTTPを使えばREST??

RESTに対する誤った理解の一つが
HTTP+JSONだけ使用すればRESTと呼ぶ場合ですが
RESTアーキテクチャをきちんと使うのはリソースをきちんと定義してこれに対するCRUDをHTTP Method POST/PUT/GET/DELETEについて合わせて使用し
エラーコードについてHTTP Response codeを使用するなど
RESTに対する属性を十分に理解してデザインしてからこそ
きちんとしたRESTスタイルだと思います。

数年前だけではなく今もAnti Patternが適用されたREST API形態がたくさんあるため
きちんとしたREST思想の理解の上、RESTを使用するようにしましょう。


### 標準規約がない

RESTは標準がない。それで管理が難しい。 

SOAP基盤のウェブサービスみたいにメッセージ構造を定義するWSDLもなく、UDDIのようなサービス管理体系もない。 WS-IやWS-*のようなメッセージ規約もない。 

RESTが最近浮上される理由自体がWebServiceの複雑性と標準の難易度のため
Non Enterprise陣営(Google、Yahoo、Amazone)を中心に集中的に紹介されたことで。
データについての意味自体がどんなビジネス要件のようにMission Criticalな要件がないため
お互いにデータを伝送できる程度の相互理解水準の標準のみが必要でEnterpriseレベルの標準が必要したこともないしベンダーのようにこれを主導する会社もなかった。

単にたくさん使用して暗黙的に生まれた標準みたいなものがあるだけ。(こんなものをDefactor標準と呼ぶ)。 

しかし、問題は正確な標準がなかったので
開発においてこれを管理することが難しいということで
いくつかのスペックに合わせて開発プロセスやパターンを作成できますが
RESTには標準がないからREST基盤でシステムを設計するためには
使用するRESTに対する独自の標準を定めなければならず
いかなる場合にはRESTに対する誤った理解で間違ったRESTアーキテクチャに'これはRESTだ'ということもある。

実際にWEB 2.0の代表走者であるFlickr.comもRESTの特性を生かすことができず
RPCスタイルでデザインしたAPIをHTTP+XMLを使用したという理由で
Hybrid RESTという名前をつけてRESTアーキテクチャに対する混乱を招いた。

最近YAMLなどと一緒にRESTに対する標準を作ろうとする動きはありますが
JSONの自由度を制約する方向で
Learning curveがやや高いためあまりの拡散がされていない。

このような非標準から来た管理の問題点は
きちんとしたREST API標準ガイドとAPI開発前後にAPI文書(Spec)をきちんとつくってレビューするプロセスを整える方法で解決する方法がいい。
かなと。思います。


## 既存の伝統的なRDBMSに適用させるに容易ではない

例えば
リソースを表現する際、リソースはDBの一つのRowになる場合が多い。
DBの場合はPrimary Keyが複合Keyの形態で存在する場合が多い。
(いくつものコラムが縛られて一つのPKになる場合)
DBでは有効な設計であるかも知れないがHTTP URIは
`/`によって階層構造を持つためこれについての表現が非常に不自然。

例えば
DBのPKが"ユーザーID"+"住む地域"+"本人の名前"の時
DBではこのように表現することがおかしくないですが
RESTでこれをuserinfo/{ユーザーID}/{住む地域}/{本人の名前}ように表現することになれば
多少変な意味。。。

以外にもresourceに対するUnique一Keyを付与することで
いろいろ問題があるが、これを解決する代案としては
Alternative Key(AK)を使用する方法がある。
意味を持っていないUnique ValueをKeyにしてDB TableにAKというフィールドにして使用する方法ですが
すでにGoogleのRESTもこのようなAKを使用するアーキテクチャを採用している。

しかし、DBにAKフィールドを追加することは全体的なDBの設計に対する変更を意味するし
これはつまりRESTのために全体システムのアーキテクチャに変化を与えるという点で
オススメではない。

それで最近は
mongoDBやCouchDB、RiakなどのDocument based NoSQLの場合
JSON Documentをそのまま入れられる構造があって
一つのドキュメントを一つのRESTリソースに取り扱うと良いですので
RESTのリソースの構造マッピングすることが容易。
(MySQL 5.7も)


## 次は

APIのセキュリティについて。



## 参照

https://martinfowler.com/articles/richardsonMaturityModel.html

https://groups.google.com/forum/?fromgroups#!forum/api-craft

