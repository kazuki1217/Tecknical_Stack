import axios from 'axios'

/**
 * API通信に使うaxiosインスタンス
 *
 * baseURLと認証ヘッダの付与をここだけで解決する（トークンの保存先やヘッダの形式を
 * 変更するとき、呼び出し元を1つも修正せずに済むようにするため）。
 */
export const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL,
})

// リクエスト時、トークンがある場合は認証ヘッダを付与する（ログイン認証や登録では不要、その他一覧取得や投稿作成・更新・削除などでは必要なため、ここで一括して付与する）
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }

  return config
})
