import { useState, ChangeEvent } from "react";

interface PostFormProps {
  onSubmit: (content: string, imageFile: File | null) => void;
}

/**
 * 投稿フォームコンポーネント
 *
 * @param onSubmit - 投稿フォームの送信時に呼び出される関数（contentと画像ファイルを引数に取る）
 * @returns JSX.Element
 */
function PostForm({ onSubmit }: PostFormProps) {
  const [content, setContent] = useState(""); // 新規投稿のテキスト情報を管理
  const [imageFile, setImageFile] = useState<File | null>(null); // 新規投稿の画像ファイルを管理

  /** 新規投稿を作成 */
  const handleSubmit = async () => {
    if (!content && !imageFile) {
      alert("テキストまたは画像のいずれかを入力してください。");
      return;
    }
    await onSubmit(content, imageFile);
    setContent("");
    setImageFile(null);
  };

  /** 画像ファイルの状態を管理 */
  const handleImageChange = (e: ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) setImageFile(file);
  };

  return (
    <div className="post-form">
      <textarea placeholder="いまどうしてる？" value={content} onChange={(e) => setContent(e.target.value)} />
      <input type="file" accept="image/*" onChange={handleImageChange} />
      <button onClick={handleSubmit}>ポストする</button>
    </div>
  );
}

export default PostForm;
