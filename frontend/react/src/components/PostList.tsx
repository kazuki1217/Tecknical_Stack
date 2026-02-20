import { useEffect, useState } from "react";
import axios from "axios";

import SidebarLayout from "./SidebarLayout";
import PostForm from "./PostForm";
import PostItem from "./PostItem";
import { createPostActions } from "../utils/createPostActions";
import { Post } from "../types/post";

/**
 * 投稿一覧画面コンポーネント
 *
 * @param loggedInUserName - ログイン中のユーザ名
 * @returns JSX.Element
 */
function PostList({ loggedInUserName }: { loggedInUserName: string | null }) {
  const [posts, setPosts] = useState<Post[]>([]); // 投稿一覧を管理

  useEffect(() => {
    fetchPosts();
  }, []);

  /** 投稿一覧を取得 */
  const fetchPosts = async () => {
    try {
      const token = localStorage.getItem("token");
      const res = await axios.get(`${import.meta.env.VITE_API_BASE_URL}/api/posts`, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });
      setPosts(res.data.data);
    } catch (error) {
      console.error("投稿一覧の取得に失敗しました:", error);
    }
  };

  const { deletePost, updatePost } = createPostActions(fetchPosts);

  /** 新規投稿を作成 */
  const submitPost = async (content: string, imageFile: File | null, tags: string) => {
    try {
      // multipart/form-data 形式に格納
      const formData = new FormData();
      content && formData.append("content", content);
      imageFile && formData.append("image", imageFile);
      tags.trim() && formData.append("tags", tags.trim());

      const token = localStorage.getItem("token");
      await axios.post(`${import.meta.env.VITE_API_BASE_URL}/api/posts`, formData, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });
      // 再度、投稿一覧を取得
      await fetchPosts();
    } catch (error) {
      console.error("新規投稿の作成に失敗しました:", error);
    }
  };

  return (
    <SidebarLayout loggedInUserName={loggedInUserName}>
      {/* 新規投稿フォーム */}
      <PostForm onSubmit={submitPost} />

      {/* 投稿一覧 */}
      <div>
        {posts.map((post) => (
          <PostItem key={post.id} post={post} loggedInUserName={loggedInUserName} onDelete={deletePost} onUpdate={updatePost} onRefresh={fetchPosts} />
        ))}
      </div>
    </SidebarLayout>
  );
}

export default PostList;
