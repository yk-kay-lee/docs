# Oauth対応と概念の整理

## 概要
RooVeRというメディアサービスに向けて
認証を統一するという話で
OAuth対応が行うところで概念と内容の整理を行う。
Identifierとは別の話で
今回のRooVeRのOAuth対応と関連性はありますが
今後の方向性については相談。(Platformとか)

## Oauth?

アプリ開発時認証と権限付与する方法についてOAuth、OpenID、SAMLなど
いくつかのスペックがあり、
この中で最も多く使用されてtwitter、facebook、googleなどメジャー企業などが
使用するスペックがOAuthです。

OAuthは現在2.0まで出ており、最終バージョン(final version)は
RFC 6749(http://tools.ietf.org/html/rfc6749)
です。

OAuth 2.0は認証及び権限付与に関する基本的な流れ(flow)だけではなく、
implementorが自分の目的に合わせて拡張できるようにしています。

このためにOAuth 2.0はプロトコルであると同時にフレームワークと呼んだりもしますね。
OAuth 2.0でサーバとクライアント間の認証を終えた場合サーバーは、
権限付与の結果としてaccess tokenを発行してくれるようになりまして
クライアントはこのaccess tokenを利用してサーバにリソースアクセスを要請することができます。

サーバはaccess tokenでリソースへの接近を許可するかどうかを決定して
結果のデータをクライアントに送信します。

サーバはaccess tokenでクライアントを認識してサービスするため、
セッション(session)やクッキー(cookie)を利用してクライアントの状態情報を維持する必要はありません。
これはREST(Representational State Transfer)デザインコンセプトの主要な要求事項である'stateless'特性を満足させてくれます。

そう。

OAuth2.0のaccess tokenを利用してサーバはRESTful APIをクライアントに提供することができます。
実際にOAuth2.0を提供するtwitter、facebook、google、yahoo、linkedinなどメジャーアプリのサーバーはaccess tokenベースのRESTful APIを提供しています。
OAuth2.0についての内容はスペック文書自体を参考してください。
今回は具体的に下記のような内容を確認したいと思います。

- アプリなどに掲載する1st party clientにOAuth2.0のどんな認証タイプを適用するのか?
- 認証および権限を付与するauthorization serverはどのように具現すれば良いか?
- 発行されたaccess tokenベースに動作するRESTful API serverはどのように具現するか?

ちょっと、ふと用語の整理が必要そうです。
観点によってアプリは次のように。

- ユーザーの観点からフロントエンド(front-end)とバックエンド(back-end)
- システムの観点からでクライアント(client)とサーバー(server)

当たり前の話ですが
アプリ開発時フロントエンドとクライアント、そしてバックエンドとサーバーは
同一の意味で使用。

クライアントは1st party clientと3rd party clientに分けられる。
1st party clientはGigがサービスのために提供する公式クライアントであり、
直接ユーザーのidとpasswordの入力を受けられる。 

そして
3rd party clientはサービスにアクセスする外部クライアントであり、
セキュリティ上の理由でユーザーから直接idとpassword入力は無し。
(id、password情報が他のアプリに保存される危険があるからということ)

OAuth2.0でサーバは
authorization serverとresource serverに分けられる。(物理的ではなく意味的に)
authorization serverはクライアントを認証してaccess tokenを発行するサーバーであります。

resource serverはクライアントのAPI要請を
access token基に処理するサーバーであり、
よく言うと普通のAPI serverです。

詳しくはOAuth 2.0のスペックを参照しましょう。

## アプリなどに掲載する1st party clientにOAuth2.0のどんな認証タイプを適用するのか

上にも簡単に言いましたが
1st party clientは特定サービスのための公式クライアントです。
例えばRooverの場合idとpasswordで会員登録、ログインもしたいということで
今回はRoover自体が1st party clientになりますし
今後Rooverのアカウントは他のサービス(3rd party)にOauth2.0として提供出来ます。

ということで
1st party clientは登録されたユーザーから
直接idとpasswordを入力してもらい、
Authorization serverを通じてユーザの認証処理を行います。
このような特性を考慮した時、
クライアント及びユーザ認証に最も適合した認証タイプは
'Resource Owner Password Credentials'
が適切ですね。

OAuth2.0のスペックでは下記の4つのタイプの認証方式を紹介しています。

- Authorization Code
- Implicit
- Resource Owner Password Credentials
- Client Credentials

簡単に核心内容を説明すると
OAuth2.0スペックでAuthorization CodeとImplicitタイプを見ると
credential client
つまり、サービスにアクセスする外部のアプリケーションのためのタイプです。

Authorization Codeは
専用のバックエンドサーバを持って動作するアプリの場合に使用する
そして
Implicitは
バックエンドサーバーがなく、クライアントサイドでのみ動作するアプリの場合に使用します。

これら外部のアプリケーションはセキュリティ上の理由で
ユーザーのidとpasswordを直接入力を受けないため
access tokenを得るためにはまず
当該サービスに移動して
ユーザ確認(ログイン)及び許可を取得しないといけません。

なので、
外部のアプリケーションは新しいwindow、webview、popupなどを生成して
該当サービスのAuthorization server urlにclient idとcallback urlをつけてリダイレクションさせます。

Authroization serverは
client idとcallback urlを検査して要請したclientが
Gigを通じて登録された正常なclientか
確認することで使用します。

そしてリダイレクションされたページで
ユーザーがid、passwordでログイン(Authentication)を行い
当該外部のアプリケーションが自分のデータをアクセスするように許可すれば
(普通'Allow'などのボタンをクリック)
Authorization serverは、当該外部のアプリケーションのcallback urlに
認証タイプによってcodeまたはaccess tokenをつけてリダイレクションします。

'Authorization Code'タイプの場合callback urlにcodeが付着し
外部のアプリケーションはこのcode値と当該サービスで発行して与えた
自分のclient idとsecretをAuthorization serverでもう一度送信して
access tokenを発行されます。

そして
'Implicit'タイプの場合
すぐcallback urlにaccess token情報がfragmentにつけて渡します。

'Authorization Code'タイプより簡素化されたタイプと思えばいいということで
clientはユーザーの端末またはPCに設置されるため
ハードコーディングされたclient id値が漏れる恐れもある。 

それでclient id値だけでaccess tokenを受け取ってくる'Implicit'タイプは
セキュリティ上やや危険ですね。

しかし'Authorization Code'タイプの場合、
Authroization serverと通信する主体がclientではなく、バックエンドサーバであり、client idとsecretがバックエンドサーバに
保存されて使用されるため
盗まれる余地がないのでさらにセキュリティ上良いかなと思います。

ちょい、複雑、長文になりましたが
とにかく
この二つのタイプの認証方式は使用上外部のアプリケーションが
Gigのサービスにアクセスするためのことにより
1st party client認証用としては適切ではなく3rd partyに適切ですね。

そして最後の
'Client Credentials'タイプは
どんなユーザーであれ関係なくて
クライアントそのものだけを認証するための方式ですので
ユーザー別で認証してaccess tokenを発行しなければならない1st party client認証用としてはやはり適切ではないですね。

最後の最後に残った
'Resource Owner Password Credentials'。
このタイプは下記のOAuth2.0スペックの説明を見ると、1st party clientに適切...に見えます。
あ〜〜〜
http://tools.ietf.org/html/rfc6749
のスペック全部英語なのはしようがないですが
はっきり
Resource Owner Password Credentialsは1st client用だ！
と書いてればよかったですね。

```rfc6749
The resource owner password credentials grant type is suitable in 
cases where the resource owner has a trust relationship with the client,
such as the device operating system or a highly privileged application (4.3)
```

1st party clientは自分のAuthozation serverに認証を要請することなので
あるページへのリダイレクション無しで、直接ユーザーのid、password入力を受けて
自分のclient idとsecretをAuthorization serverで認証およびaccess tokenを要請します。
この場合、1st party clientはユーザ端末に設置されているので
ハードコーディングされたclient idとsecretがさらされる危険があるかもしれないですが
ユーザーid、passwordだけ端末のどこかに保存しなければ安全だと思います。
(サーバー通信だとそもそも問題ない)
OAuth2.0のスペックにもaccess tokenを取得した後に
clientは必ずユーザーのcredentials
つまり、idとpasswordを除去しろー！、と言われています。

```rfc6749
The client MUST discard the credentials once an access token has been obtained. (4.3.1)
```

結論は
1st party clientには
'Resource Owner Password Credentials'
タイプを適用する!ということです。
すみません。長くなってしまって。
ですが、そうか！こういうことだなーと改めて認識する意味でしたので。

次、
3rd party clientはどうするかについて説明とauthorization serverの具現について


## 認証および権限を付与するauthorization serverはどのように具現すれば良いか
### 信頼性あるOpen Sourceを利用
- [PHP OAuth 2.0](http://oauth2.thephpleague.com/)
- composer require league/oauth2-server

http://thephpleague.com/
グループはGithubとかでも人気の各種packageを開発している非営利組織です.

その中でLeague OAuth Packageは
OAuth2.0スペックの重要なflowをPHPで具現したpackageで
様々なPHP Frameworkで使用出来ます。
詳しい内容はドキュメントを参照してください。

### AuthorizationのためのAPI設定
1st party、3rd party
すべてのclientは認証&access tokenを要請するため
urlの形のAPIを通じてアクセス接近します。
下記のAPIに4つの認証タイプの要請をすべて受け入れることができます。

(restful nameはまだ仮)
1. /oauth/authorize
2. /oauth/authorize/decision
3. /oauth/token

1、2は
'Authorization Code'と'Implicit'タイプの認証方式でのみ使用されます。
つまり
3rd party clientが使用することになり
2番APIのResponse結果が当該clientが登録する際指定したcallback urlの後ろについて渡ることになります。
Response結果は上で説明した通り
認証タイプによってcodeまたはaccess tokenのどちらかです。(今回はaccess token)

3は
'Authorization Code'、
'Resource Owner Password Credentials'、
'Client Credentials'
タイプの認証方式で使用されます。

API要請時
clientはOAuth2.0スペックで要求すれるheaderとbodyを作って要請しなければならなくてauthorization serverはこの値を解析してclient検証＆ユーザーの検証をすることで使用します。

APIの使い方についての詳しい説明は
また別のドキュメント化で説明します。

authorizationの意味通りに権限の付与をきちんとしようとすると
client、ユーザごとにアクセスできるリソースを制限する機能必要です。

このため
OAuth2.0スペックでは
'scope'というフィールドがありまして
ちゃんとauthorization serverを開発するためには
リソースに対するscopeを定義してhttp bodyの'scope'フィールドを活用する必要があります。

今回はこのscopeを利用しないですが
今後サービスが拡張するとまた検討する。という形で。

### 必須のDatabase設定
OAuth2.0スペックどおりに動作するには
基本的に下記のようなデータをAuthorization serverが持っていなければならない。

- 登録されたclientらのidとsecret情報(普通developer siteで各client開発者が登録すること、私はテストのためデフォルトで一つずつ登録しますす今回のサービスではまだまだdeveloper siteとか必要無し)
- 登録されたユーザーのidとpassword情報
- access tokenを得るために発行されたcode情報
('Authorization Code'タイプのためにのみ使用)
- 発行されたaccess token情報


### 認証タイプ別client情報登録
- client name *
- client id *
- client secret *
- token refresh 可否 *
- callback url

### 認証＆access token発行テスト
- Laravel 5.1
- Restful API
- Oauth Authorization Server実装

Resource Owner Password Credentialsタイプで
動作テストをやってみました。

このため
仮のDB & Table設定
Laravelの
Module
Controller
Middleware
Provider
Route
Csrf
など実装

結果は下記の画像を参照

![sc1.png (79.3 kB)](https://img.esa.io/uploads/production/attachments/1834/2016/03/04/7844/62525aa1-be42-476e-9736-03e3f51ca37a.png)

![sc3.png (48.7 kB)](https://img.esa.io/uploads/production/attachments/1834/2016/03/04/7844/8f17af59-f6a0-4cf1-8bf5-de1abd73ed1c.png)

ユーザーがログインすると
内部ではaccess tokenが発行され
それはサービス側でSessionとかCookieで保存され
一回ログインするといつでもapiでユーザー情報の取得が出来ます。
(実際default ttlは604800なので 7days間です)

うまく動作しますね。

ではプロトタイプはこれでOKなので
実際のサーバー具現と認証タイプ別APIサーバーを開発に進みましょー。

...
作成及び作業中

