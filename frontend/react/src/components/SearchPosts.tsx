import { useState } from "react";
import axios from "axios";
import { FaSearch } from "react-icons/fa";

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
 * 投稿検索画面コンポーネント。
 *
 * @param user - ログイン中のユーザ名
 * @returns JSX.Element
 */
function SearchPosts({ user }: { user: string | null }) {
  const [keyword, setKeyword] = useState<string>(""); // 検索キーワードを管理
  const [results, setResults] = useState<Post[]>([]); // 検索にヒットした投稿一覧を管理
  const [isComposing, setIsComposing] = useState(false); // IME入力が確定したか否かを管理（日本語入力などで入力を確定したタイミングで検索処理が実行されることを防ぐため）

  // 検索処理
  const handleSearch = async () => {
    try {
      const token = localStorage.getItem("token");
      const res = await axios.get("http://localhost:8000/api/posts/search", {
        params: { keyword },
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });
      setResults(res.data);
    } catch (error) {
      console.error("検索処理に失敗しました:", error);
    }
  };

  return (
    <SidebarLayout user={user}>
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
            {/* ユーザ名・投稿日時 */}
            <p>
              <strong>{post.user.name}</strong> ・ <span className="post-date">{formatPostDate(post.created_at)}</span>
            </p>
            {/* テキスト情報があれば表示 */}
            {post.content && <p>{post.content}</p>}
            {/* 画像ファイルがあれば表示 */}
            {post.image_base64 && <img src={post.image_base64} alt="post" className="post-img" />}
          </div>
        ))}
      </div>
    </SidebarLayout>
  );
}

export default SearchPosts;
