import { useState } from "react";
import axios from "axios";
import { FaSearch } from "react-icons/fa";

import SidebarLayout from "./SidebarLayout";
import PostItem from "./PostItem";
import "../styles/SearchPosts.css";
import { createPostActions } from "../utils/createPostActions";

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

  /** キーワード検索（部分一致）にヒットした投稿一覧を取得を取得 */
  const handleSearch = async () => {
    try {
      const token = localStorage.getItem("token");
      const res = await axios.get("http://localhost:8080/api/posts/search", {
        params: { keyword },
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });
      setResults(res.data.post);
    } catch (error) {
      console.error("検索処理に失敗しました:", error);
    }
  };

  const { deletePost, updatePost } = createPostActions(handleSearch);

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
          <PostItem key={post.id} post={post} currentUser={user} onDelete={deletePost} onUpdate={updatePost} />
        ))}
      </div>
    </SidebarLayout>
  );
}

export default SearchPosts;
