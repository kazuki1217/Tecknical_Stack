import { useState } from "react";
import { formatPostDate } from "../utils/date";

import "../styles/PostItem.css";

interface Post {
  id: number;
  user: { name: string | null };
  content: string;
  created_at: string;
  image_base64?: string | null;
}

interface PostItemProps {
  post: Post;
  currentUser: string | null;
  onDelete: (id: number) => void;
  onUpdate: (id: number, content: string) => void;
  onEditStart?: () => void;
}

/**
 * 投稿アイテムコンポーネント（投稿1件の表示および編集・削除操作）
 *
 * @param post - 表示対象の投稿データ
 * @param currentUser - 現在ログイン中のユーザ名（投稿者と一致する場合、操作ボタンを表示）
 * @param onDelete - 投稿削除時に呼び出される関数（idを引数に取る）
 * @param onUpdate - 投稿更新時に呼び出される関数（idと更新後contentを引数に取る）
 * @returns JSX.Element
 */
function PostItem({ post, currentUser, onDelete, onUpdate }: PostItemProps) {
  const [isEditing, setIsEditing] = useState(false); // 編集モードを管理
  const [editContent, setEditContent] = useState(post.content); // 編集中のテキスト情報を管理

  /** 投稿内容を更新し、編集モードを終了 */
  const handleUpdate = async () => {
    await onUpdate(post.id, editContent);
    setIsEditing(false);
  };

  return (
    <div className="post-card">
      <p className="post-header">
        {/* ユーザ名・投稿日時 */}
        <span className="post-info">
          <strong>{post.user.name}</strong> ・ <span className="post-date">{formatPostDate(post.created_at)}</span>
        </span>
        {/* 投稿者が自分の場合のみ編集ボタン・削除ボタンを表示 */}
        {post.user.name === currentUser && (
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
        </>
      )}
    </div>
  );
}

export default PostItem;
