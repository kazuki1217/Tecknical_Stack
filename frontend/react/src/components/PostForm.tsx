import { useState, ChangeEvent } from 'react'

import { useAsyncAction } from '../hooks/useAsyncAction'
import '../styles/PostForm.css'

interface PostFormProps {
  onSubmit: (
    content: string,
    imageFile: File | null,
    tags: string,
  ) => Promise<void>
}

/**
 * 投稿フォームコンポーネント
 *
 * @param onSubmit - 投稿フォームの送信時に呼び出される関数（contentと画像ファイルを引数に取る）
 * @returns JSX.Element
 */
function PostForm({ onSubmit }: PostFormProps) {
  const [content, setContent] = useState('') // 新規投稿のテキスト情報を管理
  const [imageFile, setImageFile] = useState<File | null>(null) // 新規投稿の画像ファイルを管理
  const [tags, setTags] = useState('') // 新規投稿のタグ情報（カンマ区切り）
  const submitAction = useAsyncAction('投稿の作成に失敗しました。') // 送信中か否かと、失敗時の文言を管理

  /** 新規投稿を作成 */
  const handleSubmit = async () => {
    if (!content && !imageFile) {
      alert('テキストまたは画像のいずれかを入力してください。')
      return
    }

    const isSucceeded = await submitAction.run(() =>
      onSubmit(content, imageFile, tags),
    )
    if (!isSucceeded) {
      // 失敗時は入力内容を残し、そのまま再送信できるようにする
      return
    }

    setContent('')
    setImageFile(null)
    setTags('')
  }

  /** 画像ファイルの状態を管理 */
  const handleImageChange = (e: ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0]
    if (file) setImageFile(file)
  }

  return (
    <div className="post-form">
      <textarea
        placeholder="いまどうしてる？"
        value={content}
        onChange={(e) => setContent(e.target.value)}
      />
      <input
        type="text"
        placeholder="タグをカンマ区切りで入力（例: Laravel,React）"
        value={tags}
        onChange={(e) => setTags(e.target.value)}
      />
      <input type="file" accept="image/*" onChange={handleImageChange} />
      {/* 画像付き投稿は送信完了までに待ち時間があるため、ボタンのラベルでも送信中を示す */}
      <button onClick={handleSubmit} disabled={submitAction.isRunning}>
        {submitAction.isRunning ? '送信中…' : 'ポストする'}
      </button>
      {submitAction.errorMessage && (
        <p className="post-form-error">{submitAction.errorMessage}</p>
      )}
    </div>
  )
}

export default PostForm
