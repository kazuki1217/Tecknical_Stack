import Layout from "./Layout";
import { useState } from "react";
import axios from "axios";
import { FaSearch } from "react-icons/fa";

import { formatPostDate } from "../utils/date";
import "../styles/index.css";

interface Post {
  id: number;
  user: { name: string | null };
  content: string;
  created_at: string;
}

/** 検索画面を構成 */
function SearchPosts({ user }: { user: string | null }) {
  const [keyword, setKeyword] = useState<string>(""); // 検索キーワードを管理
  const [results, setResults] = useState<Post[]>([]); // 検索結果の投稿一覧を管理
  const [isComposing, setIsComposing] = useState(false); // 日本語入力の変換中かどうかを管理

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
      user={user}
      onLogout={() => {
        localStorage.removeItem("token");
        window.location.href = "/";
      }}
    >
      {/* 検索バー */}
      <div className="search-box">
        <input
          value={keyword}
          onChange={(e) => setKeyword(e.target.value)}
          placeholder="投稿内容を検索"
          onCompositionStart={() => setIsComposing(true)}
          onCompositionEnd={() => setIsComposing(false)}
          onKeyDown={(e) => {
            if (e.key === "Enter" && !isComposing) {
              handleSearch();
            }
          }}
        />
        <button onClick={handleSearch}>
          <FaSearch />
        </button>
      </div>

      {/* 検索結果の表示 */}
      <div>
        {results.map((post) => (
          <div key={post.id} className="post-card">
            <p>
              <strong>{post.user.name}</strong> ・ <span className="post-date">{formatPostDate(post.created_at)}</span>
            </p>
            <p>{post.content}</p>
          </div>
        ))}
      </div>
    </Layout>
  );
}

export default SearchPosts;
