[api_design_guide.md](api_design_guide.md) : API DESIGN GUIDE。

[dev_before.md](dev_before.md) : プロジェクトkick off前エンジニアの皆さんへ。

[di.md](di.md) : Dependency InjectionとRepository Patternについて。

[cors.md](cors.md) : CORSについて。

[http2.md](http2.md) : http2導入。

[oauth2.md](oauth2.md) : OAUTH2導入。

[ml.md](ml.md) : Machine Learningについて。


`サンプルコード`は

Mileageを取得、アップデートなどする機能をRepository Patternで簡単に実装したものです。
CacheはLaravel Cache Contractを利用してRedis, Memcached, MongoDBなど柔軟な対応が出来るようにしていて
基本最初はmysqlからデーターの取得、更新などが行われ、次回からは指定されたCacheから取得、更新などが行う感じです。
意図などは [di.md](di.md)を参照。
