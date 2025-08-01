// Viteが提供する型定義を読み込む。
// これを書かないと、Vite専用の `import.meta.env` などがTypeScriptで認識されない。
/// <reference types="vite/client" />

// Viteで使う環境変数の型を定義する。
// ここで定義した変数だけが `import.meta.env` 経由で使えるようになる。
interface ImportMetaEnv {
  // .envファイルで定義した VITE_API_BASE_URL を読み込めるようにする。
  readonly VITE_API_BASE_URL: string;
}

// `import.meta` という特殊なオブジェクトの型定義。
// `env` プロパティの中に、上で定義した ImportMetaEnv が入ることを示す。
interface ImportMeta {
  readonly env: ImportMetaEnv;
}
