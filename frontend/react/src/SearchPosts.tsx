import Layout from "./Layout";
import { useState } from "react";
import axios from "axios";

interface Post {
  id: number;
  user: { name: string | null };
  content: string;
  created_at: string;
}

function SearchPosts() {
  const [keyword, setKeyword] = useState<string>(""); // 検索キーワードを管理
  const [results, setResults] = useState<Post[]>([]); // 検索結果の投稿一覧を管理

  // 検索処理
  const handleSearch = async () => {
    const token = localStorage.getItem("token");

    try {
      const res = await axios.get("http://localhost:8000/api/posts/search", {
        params: { keyword }, // クエリパラメータとして keyword を追加
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });

      setResults(res.data);
    } catch (error) {
      console.error("検索失敗:", error);
    }
  };

  return (
    <Layout
      onLogout={() => {
        localStorage.removeItem("token");
        window.location.href = "/";
      }}
    >
      <h1>投稿検索</h1>
      {/* 検索ボックス */}
      <input type="text" value={keyword} onChange={(e) => setKeyword(e.target.value)} placeholder="投稿内容を検索" style={{ width: "300px", marginRight: "10px" }} />
      {/* 検索ボタン */}
      <button onClick={handleSearch}>検索</button>

      {/* 検索結果の表示 */}
      <div style={{ marginTop: "2rem" }}>
        {results.map((post) => (
          <div key={post.id} style={{ border: "1px solid #ccc", marginBottom: "1rem", padding: "0.5rem" }}>
            <p>
              <strong>{post.user.name}</strong> - {new Date(post.created_at).toLocaleString()}
            </p>
            <p>{post.content}</p>
          </div>
        ))}
      </div>
    </Layout>
  );
}

export default SearchPosts;
