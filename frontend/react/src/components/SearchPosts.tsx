import { useState } from "react";
import axios from "axios";
import { FaSearch } from "react-icons/fa";

import SidebarLayout from "./SidebarLayout";
import PostItem from "./PostItem";

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
      await handleSearch();
    } catch (error) {
      console.error("投稿の削除に失敗しました:", error);
    }
  };

  /** 投稿内容を更新 */
  const updatePost = async (id: number, content: string) => {
    try {
      const token = localStorage.getItem("token");
      await axios.put(
        `http://localhost:8000/api/posts/${id}`,
        { content },
        {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        }
      );
      // 再度、投稿一覧を取得
      await handleSearch();
    } catch (error) {
      console.error("投稿内容の更新に失敗しました:", error);
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
          <PostItem key={post.id} post={post} currentUser={user} onDelete={deletePost} onUpdate={updatePost} />
        ))}
      </div>
    </SidebarLayout>
  );
}

export default SearchPosts;
