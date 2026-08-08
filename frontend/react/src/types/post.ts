export interface Tag {
  id: number
  name: string
}

export interface User {
  id: number
  name: string | null
}

export interface Comment {
  id: number
  content: string
  created_at: string
  user: User
}

/** 投稿データ */
export interface Post {
  /** 投稿ID */
  id: number

  /** 投稿者 */
  user: User

  /** 投稿本文 */
  content: string

  /** 投稿日時 */
  created_at: string

  /** Base64化された画像。画像がない場合はnull */
  image_base64?: string | null

  /** 投稿に紐づくタグ一覧 */
  tags: Tag[]

  /** 投稿に紐づくコメント一覧 */
  comments: Comment[]
}

/** 一覧のページ情報 */
export interface PaginationMeta {
  /** 現在のページ番号。リクエストで渡したpageがそのまま返すため、last_pageを超える値になることがある */
  current_page: number

  /** 最終ページの番号（＝総ページ数）。例: 103件を20件ずつなら6 */
  last_page: number

  /** 1ページあたりの件数 */
  per_page: number

  /** 全体の件数 */
  total: number

  /** 現在のページの先頭が全体の何件目か。該当する投稿が無い場合はnull */
  from: number | null

  /** 現在のページの末尾が全体の何件目か。該当する投稿が無い場合はnull */
  to: number | null

  /** 次のページがあるか */
  has_more_pages: boolean
}

/** ページ情報の初期値。取得前の状態と、レスポンスに meta が含まれない場合の既定値に使う */
export const INITIAL_PAGINATION_META: PaginationMeta = {
  current_page: 1,
  last_page: 1,
  per_page: 20,
  total: 0,
  from: null,
  to: null,
  has_more_pages: false,
}
