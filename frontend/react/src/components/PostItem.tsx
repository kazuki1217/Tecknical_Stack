import { useState } from "react";
import axios from "axios";
import { formatPostDate } from "../utils/date";
import { Post } from "../types/post";

import "../styles/PostItem.css";

interface PostItemProps {
  post: Post;
  loggedInUserName: string | null;
  onDelete: (id: number) => void;
  onUpdate: (id: number, content: string) => void;
  onRefresh: () => Promise<void>;
  onEditStart?: () => void;
}

/**
 * 投稿アイテムコンポーネント（投稿1件の表示および編集・削除・コメント操作）
 *
 * @param post - 表示対象の投稿データ
 * @param loggedInUserName - 現在ログイン中のユーザ名（投稿者と一致する場合、操作ボタンを表示）
 * @param onDelete - 投稿削除時に呼び出される関数（idを引数に取る）
 * @param onUpdate - 投稿更新時に呼び出される関数（idと更新後contentを引数に取る）
 * @param onRefresh - コメントの追加/削除後に再取得する関数
 * @returns JSX.Element
 */
function PostItem({ post, loggedInUserName, onDelete, onUpdate, onRefresh }: PostItemProps) {
  const [isEditing, setIsEditing] = useState(false); // 編集モードを管理
  const [editContent, setEditContent] = useState(post.content); // 編集中のテキスト情報を管理
  const [commentContent, setCommentContent] = useState(""); // 新規コメント入力

  /** 投稿内容を更新し、編集モードを終了 */
  const handleUpdate = async () => {
    await onUpdate(post.id, editContent);
    setIsEditing(false);
  };

  /** コメントを追加 */
  const handleCommentSubmit = async () => {
    const content = commentContent.trim();
    if (!content) {
      return;
    }

    try {
      const token = localStorage.getItem("token");
      await axios.post(
        `${import.meta.env.VITE_API_BASE_URL}/api/posts/${post.id}/comments`,
        { content },
        {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        }
      );

      setCommentContent("");
      await onRefresh();
    } catch (error) {
      console.error("コメントの追加に失敗しました:", error);
    }
  };

  /** コメントを削除 */
  const handleCommentDelete = async (commentId: number) => {
    try {
      const token = localStorage.getItem("token");
      await axios.delete(`${import.meta.env.VITE_API_BASE_URL}/api/comments/${commentId}`, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });
      await onRefresh();
    } catch (error) {
      console.error("コメントの削除に失敗しました:", error);
    }
  };

  return (
    <div className="post-card">
      <p className="post-header">
        {/* ユーザ名・投稿日時 */}
        <span className="post-info">
          <strong>{post.user.name}</strong> ・ <span className="post-date">{formatPostDate(post.created_at)}</span>
        </span>
        {/* 投稿者が自分の場合のみ編集ボタン・削除ボタンを表示 */}
        {post.user.name === loggedInUserName && (
          <span className="post-actions">
            <button onClick={() => setIsEditing(true)} className="edit-button">
              編集
            </button>
            <button onClick={() => onDelete(post.id)} className="delete-button">
              削除
            </button>
          </span>
        )}
      </p>
      {/* 編集モードの投稿は、テキスト入力フィールド・キャンセルボタン・更新するボタンを表示 */}
      {isEditing ? (
        <>
          <textarea className="edit-textarea" rows={4} cols={50} value={editContent} onChange={(e) => setEditContent(e.target.value)} />
          <br />
          <button className="edit-cancel-button" onClick={() => setIsEditing(false)}>
            キャンセル
          </button>
          <button className="edit-update-button" onClick={handleUpdate}>
            更新する
          </button>
        </>
      ) : (
        <>
          {post.content && <p>{post.content}</p>}
          {post.image_base64 && <img src={post.image_base64} alt="post" className="post-img" />}

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
            <p className="post-comments-title">コメント ({post.comments.length})</p>

            {post.comments.map((comment) => (
              <div key={comment.id} className="post-comment-item">
                <span>
                  <strong>{comment.user.name}</strong>: {comment.content}
                </span>
                {comment.user.name === loggedInUserName && (
                  <button className="comment-delete-button" onClick={() => handleCommentDelete(comment.id)}>
                    削除
                  </button>
                )}
              </div>
            ))}

            <div className="post-comment-form">
              <input
                type="text"
                placeholder="コメントを書く"
                value={commentContent}
                onChange={(e) => setCommentContent(e.target.value)}
                onKeyDown={(e) => {
                  if (e.key === "Enter") {
                    handleCommentSubmit();
                  }
                }}
              />
              <button onClick={handleCommentSubmit}>送信</button>
            </div>
          </div>
        </>
      )}
    </div>
  );
}

export default PostItem;
