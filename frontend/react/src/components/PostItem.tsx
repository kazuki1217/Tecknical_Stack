import { useState } from 'react'
import { api } from '../lib/api'
import { formatPostDate } from '../utils/date'
import { useAsyncAction } from '../hooks/useAsyncAction'
import { Post } from '../types/post'

import '../styles/PostItem.css'

interface PostItemProps {
  post: Post
  loggedInUserId: number | null
  onRefresh: () => Promise<void>
}

/**
 * 投稿アイテムコンポーネント（投稿1件の表示および編集・削除・コメント操作）
 *
 * 投稿とコメントの書き込み処理はいずれもこのコンポーネント内で実行し、反映は onRefresh に任せる
 * （操作の実体と、無効化・失敗表示を行うボタンを同じファイルで追えるようにするため）。
 *
 * @param post - 表示対象の投稿データ
 * @param loggedInUserId - 現在ログイン中のユーザーID（投稿者と一致する場合、操作ボタンを表示）
 * @param onRefresh - 投稿の更新・削除、コメントの追加・削除の後に一覧を再取得する関数
 * @returns JSX.Element
 */
function PostItem({ post, loggedInUserId, onRefresh }: PostItemProps) {
  const [isEditing, setIsEditing] = useState(false) // 編集モードを管理
  const [editContent, setEditContent] = useState(post.content) // 編集中のテキスト情報を管理
  const [commentContent, setCommentContent] = useState('') // 新規コメント入力

  // 書き込み処理ごとに実行状態を持つ（例: コメント送信中でも投稿の編集は操作できるようにするため）
  const updateAction = useAsyncAction('投稿の更新に失敗しました。')
  const deleteAction = useAsyncAction('投稿の削除に失敗しました。')
  const commentSubmitAction = useAsyncAction('コメントの追加に失敗しました。')
  const commentDeleteAction = useAsyncAction('コメントの削除に失敗しました。')

  /** 投稿内容を更新し、編集モードを終了 */
  const handleUpdate = async () => {
    const isSucceeded = await updateAction.run(async () => {
      await api.patch(`/api/posts/${post.id}`, { content: editContent })

      await onRefresh()
    })

    // 失敗時は編集内容を残したまま編集モードを維持し、そのまま再実行できるようにする
    if (isSucceeded) {
      setIsEditing(false)
    }
  }

  /** 投稿を削除 */
  const handleDelete = async () => {
    await deleteAction.run(async () => {
      await api.delete(`/api/posts/${post.id}`)

      await onRefresh()
    })
  }

  /** コメントを追加 */
  const handleCommentSubmit = async () => {
    const content = commentContent.trim()
    if (!content) {
      return
    }

    const isSucceeded = await commentSubmitAction.run(async () => {
      await api.post(`/api/posts/${post.id}/comments`, { content })

      await onRefresh()
    })

    // 失敗時は入力内容を残し、そのまま再送信できるようにする
    if (isSucceeded) {
      setCommentContent('')
    }
  }

  /** コメントを削除 */
  const handleCommentDelete = async (commentId: number) => {
    await commentDeleteAction.run(async () => {
      await api.delete(`/api/comments/${commentId}`)
      await onRefresh()
    })
  }

  return (
    <div className="post-card">
      <p className="post-header">
        {/* ユーザ名・投稿日時 */}
        <span className="post-info">
          <strong>{post.user.name}</strong> ・{' '}
          <span className="post-date">{formatPostDate(post.created_at)}</span>
        </span>
        {/* 投稿者が自分の場合のみ編集ボタン・削除ボタンを表示 */}
        {post.user.id === loggedInUserId && (
          <span className="post-actions">
            <button
              onClick={() => setIsEditing(true)}
              className="edit-button"
              disabled={deleteAction.isRunning}
            >
              編集
            </button>
            <button
              onClick={handleDelete}
              className="delete-button"
              disabled={deleteAction.isRunning}
            >
              削除
            </button>
          </span>
        )}
      </p>
      {deleteAction.errorMessage && (
        <p className="post-action-error">{deleteAction.errorMessage}</p>
      )}
      {/* 編集モードの投稿は、テキスト入力フィールド・キャンセルボタン・更新するボタンを表示 */}
      {isEditing ? (
        <>
          <textarea
            className="edit-textarea"
            rows={4}
            cols={50}
            value={editContent}
            onChange={(e) => setEditContent(e.target.value)}
          />
          <br />
          <button
            className="edit-cancel-button"
            onClick={() => setIsEditing(false)}
            disabled={updateAction.isRunning}
          >
            キャンセル
          </button>
          <button
            className="edit-update-button"
            onClick={handleUpdate}
            disabled={updateAction.isRunning}
          >
            更新する
          </button>
          {updateAction.errorMessage && (
            <p className="post-action-error">{updateAction.errorMessage}</p>
          )}
        </>
      ) : (
        <>
          {post.content && <p>{post.content}</p>}
          {post.image_base64 && (
            <img src={post.image_base64} alt="post" className="post-img" />
          )}

          {post.tags.length > 0 && (
            <div className="post-tags">
              {post.tags.map((tag) => (
                <span key={tag.id} className="post-tag">
                  #{tag.name}
                </span>
              ))}
            </div>
          )}

          <div className="post-comments">
            <p className="post-comments-title">
              コメント ({post.comments.length})
            </p>

            {post.comments.map((comment) => (
              <div key={comment.id} className="post-comment-item">
                <span>
                  <strong>{comment.user.name}</strong>: {comment.content}
                </span>
                {comment.user.id === loggedInUserId && (
                  <button
                    className="comment-delete-button"
                    onClick={() => handleCommentDelete(comment.id)}
                    // 削除中はどのコメントの削除も受け付けない（実行状態はコメント単位ではなく投稿単位で持つため）
                    disabled={commentDeleteAction.isRunning}
                  >
                    削除
                  </button>
                )}
              </div>
            ))}

            {commentDeleteAction.errorMessage && (
              <p className="post-action-error">
                {commentDeleteAction.errorMessage}
              </p>
            )}

            <div className="post-comment-form">
              <input
                type="text"
                placeholder="コメントを書く"
                value={commentContent}
                onChange={(e) => setCommentContent(e.target.value)}
                onKeyDown={(e) => {
                  // 送信中はEnterでも再送信させない（ボタン以外の経路で二重に登録されるのを防ぐため）
                  if (e.key === 'Enter' && !commentSubmitAction.isRunning) {
                    handleCommentSubmit()
                  }
                }}
              />
              <button
                onClick={handleCommentSubmit}
                disabled={commentSubmitAction.isRunning}
              >
                送信
              </button>
            </div>
            {commentSubmitAction.errorMessage && (
              <p className="post-action-error">
                {commentSubmitAction.errorMessage}
              </p>
            )}
          </div>
        </>
      )}
    </div>
  )
}

export default PostItem
