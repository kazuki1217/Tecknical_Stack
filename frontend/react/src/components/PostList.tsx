import { useNavigate } from "react-router-dom";
import { useEffect, useState, ChangeEvent } from "react";
import axios from "axios";

import SidebarLayout from "./SidebarLayout";
import { formatPostDate } from "../utils/date";

interface Post {
  id: number;
  user: { name: string | null };
  content: string;
  created_at: string;
  image_base64?: string | null;
}

/**
 * 投稿一覧画面コンポーネント
 *
 * @param user - ログイン中のユーザ名
 * @returns JSX.Element
 */
function PostList({ user }: { user: string | null }) {
  const navigate = useNavigate();
  const [posts, setPosts] = useState<Post[]>([]); // 投稿一覧を管理
  const [content, setContent] = useState(""); // 新規投稿のテキスト情報を管理
  const [imageFile, setImageFile] = useState<File | null>(null); // 新規投稿の画像ファイルを管理
  const [editingPostId, setEditingPostId] = useState<number | null>(null); // 編集中の投稿IDを管理
  const [editContent, setEditContent] = useState<string>(""); // 編集中のテキスト情報を管理

  useEffect(() => {
    fetchPosts();
  }, []);

  /** 投稿一覧を取得 */
  const fetchPosts = async () => {
    try {
      const token = localStorage.getItem("token");
      const res = await axios.get("http://localhost:8000/api/posts", {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });
      setPosts(res.data);
    } catch (error) {
      console.error("投稿一覧取得エラー:", error);
    }
  };

  /** 画像ファイルの状態を管理 */
  const handleImageChange = (e: ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      setImageFile(file);
    }
  };

  /** 新規投稿を作成 */
  const submitPost = async () => {
    if (!content && !imageFile) {
      alert("テキストまたは画像のいずれかを入力してください。");
      return;
    }
    try {
      // multipart/form-data 形式に格納
      const formData = new FormData();
      content && formData.append("content", content);
      imageFile && formData.append("image", imageFile);

      const token = localStorage.getItem("token");
      const res = await axios.post("http://localhost:8000/api/posts", formData, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });
      // 再度、投稿一覧を取得
      await fetchPosts();
      setContent("");
      setImageFile(null);
    } catch (error) {
      console.error("新規投稿の作成に失敗しました:", error);
    }
  };

  /** 指定したIDの投稿を削除する */
  const deletePost = async (id: number) => {
    try {
      const token = localStorage.getItem("token");
      await axios.delete(`http://localhost:8000/api/posts/${id}`, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });
      // 再度、投稿一覧を取得
      await fetchPosts();
    } catch (error) {
      console.error("投稿の削除に失敗しました:", error);
    }
  };

  /** 編集モードを開始 */
  const startEdit = (post: Post) => {
    setEditingPostId(post.id);
    setEditContent(post.content);
  };

  /** 投稿内容を更新 */
  const updatePost = async (id: number) => {
    try {
      const token = localStorage.getItem("token");
      const res = await axios.put(
        `http://localhost:8000/api/posts/${id}`,
        { content: editContent },
        {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        }
      );
      // 再度、投稿一覧を取得
      await fetchPosts();
      setEditingPostId(null);
      setEditContent("");
    } catch (error) {
      console.error("更新失敗:", error);
    }
  };

  return (
    <SidebarLayout user={user}>
      {/* 新規投稿フォーム */}
      <div className="post-form">
        <textarea placeholder="いまどうしてる？" value={content} onChange={(e) => setContent(e.target.value)} />
        <input type="file" accept="image/*" onChange={handleImageChange} />
        <button onClick={submitPost}>ポストする</button>
      </div>

      {/* 投稿一覧 */}
      <div>
        {posts.map((post) => (
          <div key={post.id} className="post-card">
            <p className="post-header">
              {/* ユーザ名・投稿日時 */}
              <span className="post-info">
                <strong>{post.user.name}</strong> ・ <span className="post-date">{formatPostDate(post.created_at)}</span>
              </span>
              {/* 投稿者が自分の場合のみ編集ボタン・削除ボタンを表示 */}
              {post.user.name === user && (
                <span className="post-actions">
                  <button onClick={() => startEdit(post)} className="edit-button">
                    編集
                  </button>
                  <button onClick={() => deletePost(post.id)} className="delete-button">
                    削除
                  </button>
                </span>
              )}
            </p>

            {/* 編集モードの投稿は、テキスト入力フィールド・キャンセルボタン・更新するボタンを表示 */}
            {editingPostId === post.id ? (
              <>
                <textarea className="edit-textarea" rows={4} cols={50} value={editContent} onChange={(e) => setEditContent(e.target.value)} />
                <br />
                <button className="edit-cancel-button" onClick={() => setEditingPostId(null)}>
                  キャンセル
                </button>
                <button className="edit-update-button" onClick={() => updatePost(post.id)}>
                  更新する
                </button>
              </>
            ) : (
              <>
                {/* テキスト情報があれば表示 */}
                {post.content && <p>{post.content}</p>}
                {/* 画像ファイルがあれば表示 */}
                {post.image_base64 && <img src={post.image_base64} alt="post" className="post-img" />}
              </>
            )}
          </div>
        ))}
      </div>
    </SidebarLayout>
  );
}

export default PostList;
