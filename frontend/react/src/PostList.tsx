import Layout from "./Layout";
import { useNavigate } from "react-router-dom";
import { useEffect, useState } from "react";
import axios from "axios";

interface Post {
  id: number;
  user: { name: string | null };
  content: string;
  created_at: string;
}

function PostList({ user }: { user: string | null }) {
  const navigate = useNavigate();
  const [posts, setPosts] = useState<Post[]>([]);
  const [content, setContent] = useState("");

  // 投稿一覧を取得
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

  // 新規投稿を送信
  const submitPost = async () => {
    if (!content) return;

    try {
      const token = localStorage.getItem("token");
      const res = await axios.post(
        "http://localhost:8000/api/posts",
        { content },
        {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        }
      );
      setPosts([res.data, ...posts]);
      setContent("");
    } catch (error) {
      console.error("投稿送信エラー:", error);
    }
  };

  // 投稿の削除
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

  useEffect(() => {
    fetchPosts();
  }, []);

  // ログアウト処理
  const handleLogout = () => {
    localStorage.removeItem("token");
    navigate("/");
    window.location.reload(); // App.tsx を再評価させて状態をリセット
  };

  return (
    <Layout onLogout={handleLogout}>
      <h1>こんにちは「{user}」さん</h1>

      {/* 投稿フォーム */}
      <div style={{ marginTop: "2rem", marginBottom: "1rem" }}>
        <textarea rows={4} cols={50} value={content} onChange={(e) => setContent(e.target.value)} />
        <br />
        <button onClick={submitPost}>ポストする</button>
      </div>

      {/* 投稿一覧 */}
      <div>
        {posts.map((post) => (
          <div key={post.id} style={{ border: "1px solid #ccc", margin: "1rem 0", padding: "0.5rem" }}>
            <p>
              <strong>{post.user.name}</strong> - {new Date(post.created_at).toLocaleString()}
            </p>
            <p>{post.content}</p>

            {/* 自分の投稿だけ削除ボタンを表示 */}
            {post.user.name === user && (
              <button onClick={() => deletePost(post.id)} style={{ color: "white", backgroundColor: "red", border: "none", padding: "0.5rem", marginTop: "0.5rem" }}>
                削除
              </button>
            )}
          </div>
        ))}
      </div>
    </Layout>
  );
}

export default PostList;
