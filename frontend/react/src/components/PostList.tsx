import Layout from "./Layout";
import { useNavigate } from "react-router-dom";
import { useEffect, useState } from "react";
import axios from "axios";
import { ChangeEvent } from "react";

import { formatPostDate } from "../utils/date";
import "../styles/index.css";

interface Post {
  id: number;
  user: { name: string | null };
  content: string;
  created_at: string;
  image_base64?: string | null;
}

/** 投稿一覧画面を構成 */
function PostList({ user }: { user: string | null }) {
  const navigate = useNavigate();
  const [posts, setPosts] = useState<Post[]>([]); // 投稿一覧を管理
  const [content, setContent] = useState(""); // 新規投稿入力欄を管理
  const [editingPostId, setEditingPostId] = useState<number | null>(null); // 編集中の投稿IDを管理
  const [editContent, setEditContent] = useState<string>("");
  const [imageFile, setImageFile] = useState<File | null>(null);

  /** ファイル選択時の処理 */
  const handleImageChange = (e: ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      setImageFile(file);
    }
  };

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

  /** 新規投稿を作成 */
  const submitPost = async () => {
    if (!content && !imageFile) {
      alert("テキストまたは画像のいずれかを入力してください。");
      return;
    }

    try {
      const token = localStorage.getItem("token");

      // multipart/form-data 形式に格納
      const formData = new FormData();
      content && formData.append("content", content);
      imageFile && formData.append("image", imageFile);

      const res = await axios.post("http://localhost:8000/api/posts", formData, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });

      setPosts([res.data, ...posts]);
      setContent("");
      setImageFile(null);
    } catch (error) {
      console.error("投稿送信エラー:", error);
    }
  };

  /** 投稿を削除 */
  const deletePost = async (id: number) => {
    try {
      const token = localStorage.getItem("token");
      await axios.delete(`http://localhost:8000/api/posts/${id}`, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });
      // 投稿を画面から削除
      setPosts(posts.filter((p) => p.id !== id));
    } catch (error) {
      console.error("削除失敗:", error);
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

      setPosts(posts.map((p) => (p.id === id ? res.data.post : p))); // posts の一覧を更新
      setEditingPostId(null); // 編集モード終了
      setEditContent(""); // 編集内容をクリア
    } catch (error) {
      console.error("更新失敗:", error);
    }
  };

  useEffect(() => {
    fetchPosts();
  }, []);

  /** ログアウト処理 */
  const handleLogout = () => {
    localStorage.removeItem("token");
    navigate("/");
    window.location.reload(); // App.tsx を再評価させて状態をリセット
  };

  return (
    <Layout user={user} onLogout={handleLogout}>
      {/* 投稿フォーム */}
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
              <span className="post-info">
                <strong>{post.user.name}</strong> ・ <span className="post-date">{formatPostDate(post.created_at)}</span>
              </span>
              {/* 投稿者が自分の場合のみ編集・削除ボタンを表示 */}
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

            {/* 編集モードかどうかを判定 */}
            {editingPostId === post.id ? (
              <>
                <textarea className="edit-textarea" rows={4} cols={50} value={editContent} onChange={(e) => setEditContent(e.target.value)} />
                <br />
                <button className="edit-cancel-button" onClick={() => setEditingPostId(null)}>キャンセル</button>
                <button  className="edit-update-button" onClick={() => updatePost(post.id)}>更新する</button>
              </>
            ) : (
              <>
                {/* 投稿内容表示 */}
                {/* テキストがあれば表示 */}
                {post.content && <p>{post.content}</p>}
                {/* 画像があれば表示 */}
                {post.image_base64 && <img src={post.image_base64} alt="post" className="post-img" />}
              </>
            )}
          </div>
        ))}
      </div>
    </Layout>
  );
}

export default PostList;
