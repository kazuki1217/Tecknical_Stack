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

// レスポンス時、トークンを付けて送ったうえで401が返った場合、そのトークンは失効しているため破棄する
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (
      axios.isAxiosError(error) &&
      error.response?.status === 401 &&
      error.config?.headers.Authorization
    ) {
      localStorage.removeItem('token')
    }

    // 失敗として呼び出し元へ渡し直す（失敗時の表示は各画面のcatchで行うため）
    return Promise.reject(error)
  },
)
