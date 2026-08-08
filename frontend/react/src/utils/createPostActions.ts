import axios from 'axios'

/**
 * 投稿の操作（更新・削除）を提供する関数
 *
 * 通信に失敗した場合は例外をそのまま呼び出し元へ伝える（失敗の表示と実行状態の管理は、
 * ボタンを持つコンポーネント側の useAsyncAction で行うため）。
 *
 * @param onAfterChange - 更新や削除後に再実行される処理（例: 投稿一覧の再取得）
 * @returns deletePost および updatePost 関数を含むオブジェクト
 */
export function createPostActions(onAfterChange: () => Promise<void>) {
  /** 指定したIDの投稿を削除する */
  const deletePost = async (id: number) => {
    const token = localStorage.getItem('token')
    await axios.delete(`${import.meta.env.VITE_API_BASE_URL}/api/posts/${id}`, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
    await onAfterChange() // 投稿一覧の再取得など
  }

  /** 投稿内容を更新 */
  const updatePost = async (id: number, content: string) => {
    const token = localStorage.getItem('token')
    await axios.patch(
      `${import.meta.env.VITE_API_BASE_URL}/api/posts/${id}`,
      { content },
      {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      },
    )
    await onAfterChange() // 投稿一覧の再取得など
  }

  return { deletePost, updatePost }
}
