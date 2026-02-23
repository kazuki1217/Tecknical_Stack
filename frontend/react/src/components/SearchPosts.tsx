import { useState } from "react";
import axios from "axios";
import { FaSearch } from "react-icons/fa";

import SidebarLayout from "./SidebarLayout";
import PostItem from "./PostItem";
import "../styles/SearchPosts.css";
import { createPostActions } from "../utils/createPostActions";
import { Post } from "../types/post";

/**
 * 投稿検索画面コンポーネント。
 *
 * @param loggedInUserName - ログイン中のユーザ名
 * @returns JSX.Element
 */
function SearchPosts({ loggedInUserName }: { loggedInUserName: string | null }) {
  const [content, setContent] = useState<string>(""); // 検索キーワードやハッシュタグを管理
  const [results, setResults] = useState<Post[]>([]); // 検索にヒットした投稿一覧を管理
  const [isComposing, setIsComposing] = useState(false); // IME入力が確定したか否かを管理（日本語入力などで入力を確定したタイミングで検索処理が実行されることを防ぐため）

  /** キーワード検索やハッシュタグ検索にヒットした投稿一覧を取得 */
  const handleSearch = async () => {
    try {
      const token = localStorage.getItem("token");
      const res = await axios.get(`${import.meta.env.VITE_API_BASE_URL}/api/posts/search`, {
        params: { content: content },
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });
      setResults(res.data.data);
    } catch (error) {
      console.error("検索処理に失敗しました:", error);
    }
  };

  const { deletePost, updatePost } = createPostActions(handleSearch);

  return (
    <SidebarLayout loggedInUserName={loggedInUserName}>
      {/* 検索バー */}
      <div className="search-box">
        <input
          value={content}
          onChange={(e) => setContent(e.target.value)}
          placeholder="投稿内容 / #タグ で検索（例: 植物 #夜）"
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
          <PostItem key={post.id} post={post} loggedInUserName={loggedInUserName} onDelete={deletePost} onUpdate={updatePost} onRefresh={handleSearch} />
        ))}
      </div>
    </SidebarLayout>
  );
}

export default SearchPosts;
