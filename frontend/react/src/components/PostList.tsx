import { useEffect, useState } from "react";
import axios from "axios";

import SidebarLayout from "./SidebarLayout";
import PostForm from "./PostForm";
import PostItem from "./PostItem";
import { createPostActions } from "../utils/createPostActions";

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
  const [posts, setPosts] = useState<Post[]>([]); // 投稿一覧を管理

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
      setPosts(res.data.data);
    } catch (error) {
      console.error("投稿一覧の取得に失敗しました:", error);
    }
  };

  const { deletePost, updatePost } = createPostActions(fetchPosts);

  /** 新規投稿を作成 */
  const submitPost = async (content: string, imageFile: File | null) => {
    try {
      // multipart/form-data 形式に格納
      const formData = new FormData();
      content && formData.append("content", content);
      imageFile && formData.append("image", imageFile);

      const token = localStorage.getItem("token");
      await axios.post("http://localhost:8000/api/posts", formData, {
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
    <SidebarLayout user={user}>
      {/* 新規投稿フォーム */}
      <PostForm onSubmit={submitPost} />

      {/* 投稿一覧 */}
      <div>
        {posts.map((post) => (
          <PostItem key={post.id} post={post} currentUser={user} onDelete={deletePost} onUpdate={updatePost} />
        ))}
      </div>
    </SidebarLayout>
  );
}

export default PostList;
