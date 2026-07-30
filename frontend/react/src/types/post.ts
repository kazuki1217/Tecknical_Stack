export interface Tag {
  id: number;
  name: string;
}

export interface User {
  id: number;
  name: string | null;
}

export interface Comment {
  id: number;
  content: string;
  created_at: string;
  user: User;
}

/** 投稿データ */
export interface Post {
  /** 投稿ID */
  id: number;

  /** 投稿者 */
  user: User;

  /** 投稿本文 */
  content: string;

  /** 投稿日時 */
  created_at: string;

  /** Base64化された画像。画像がない場合はnull */
  image_base64?: string | null;

  /** 投稿に紐づくタグ一覧 */
  tags: Tag[];

  /** 投稿に紐づくコメント一覧 */
  comments: Comment[];
}

export interface PaginationMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
  has_more_pages: boolean;
}
