import { useCallback, useEffect, useRef, useState } from 'react'

import { api } from '../lib/api'
import SidebarLayout from './SidebarLayout'
import PostForm from './PostForm'
import PostItem from './PostItem'
import Pagination from './Pagination'
import { buildApiErrorMessage } from '../utils/apiErrorMessage'
import { INITIAL_PAGINATION_META, PaginationMeta, Post } from '../types/post'
import '../styles/PostList.css'

/** 投稿一覧取得の実行状態（未実行 / 実行中 / 成功 / 失敗） */
type FetchStatus = 'idle' | 'running' | 'success' | 'error'

/**
 * 投稿一覧画面コンポーネント
 *
 * @param loggedInUserId - ログイン中のユーザーID
 * @param loggedInUserName - ログイン中のユーザ名
 * @returns JSX.Element
 */
function PostList({
  loggedInUserId,
  loggedInUserName,
}: {
  loggedInUserId: number | null
  loggedInUserName: string | null
}) {
  const [posts, setPosts] = useState<Post[]>([]) // 投稿一覧を管理
  const [paginationMeta, setPaginationMeta] = useState<PaginationMeta>(
    INITIAL_PAGINATION_META,
  ) // ページ情報を管理
  const [fetchStatus, setFetchStatus] = useState<FetchStatus>('idle') // 投稿一覧の取得状況を管理
  const [fetchErrorMessage, setFetchErrorMessage] = useState<string | null>(
    null,
  ) // 取得失敗時に画面へ表示する文言を管理
  const currentPageRef = useRef(INITIAL_PAGINATION_META.current_page) // 削除・更新後に現在ページを再取得するため保持

  /**
   * 投稿一覧を取得する
   *
   * @param page - 取得するページ番号。省略時は現在表示中のページを取り直す
   */
  const fetchPosts = useCallback(async (page = currentPageRef.current) => {
    setFetchStatus('running')
    setFetchErrorMessage(null)

    try {
      const requestPage = (targetPage: number) =>
        api.get('/api/posts', {
          params: {
            page: targetPage,
          },
        })

      let res = await requestPage(page)
      let nextMeta = res.data.meta ?? INITIAL_PAGINATION_META
      if (nextMeta.total > 0 && nextMeta.current_page > nextMeta.last_page) {
        // 削除で最終ページが減った場合は、空ページを表示せず最後のページを取り直す
        res = await requestPage(nextMeta.last_page)
        nextMeta = res.data.meta ?? INITIAL_PAGINATION_META
      }

      // 取得結果を反映してから success にする（順序が逆だと、反映前の空配列で
      // 「投稿がまだありません。」が一瞬表示されるため）
      setPosts(res.data.data)
      currentPageRef.current = nextMeta.current_page
      setPaginationMeta(nextMeta)
      setFetchStatus('success')
    } catch (error) {
      console.error('投稿一覧の取得に失敗しました:', error)
      setFetchErrorMessage(
        buildApiErrorMessage(error, '投稿一覧の取得に失敗しました。'),
      )
      setFetchStatus('error')
    }
  }, [])

  useEffect(() => {
    fetchPosts(1)
  }, [fetchPosts])

  /**
   * 新規投稿を作成する
   *
   * 通信に失敗した場合は例外をそのまま呼び出し元へ伝える（失敗の表示と送信中の管理は、
   * 送信ボタンを持つ PostForm 側で行うため）。
   */
  const submitPost = async (
    content: string,
    imageFile: File | null,
    tags: string,
  ) => {
    // multipart/form-data 形式に格納
    const formData = new FormData()
    if (content) {
      formData.append('content', content)
    }
    if (imageFile) {
      formData.append('image', imageFile)
    }
    if (tags.trim()) {
      formData.append('tags', tags.trim())
    }

    await api.post('/api/posts', formData)
    // 新規投稿は新着順の先頭に表示されるため、1ページ目を再取得する
    await fetchPosts(1)
  }

  // 初回描画時（idle）は取得が始まる直前のため、読み込み中と同じ扱いにする
  const isLoadingPosts = fetchStatus === 'idle' || fetchStatus === 'running'
  const hasPosts = fetchStatus === 'success' && posts.length > 0

  /** 通信状態に応じて「読み込み中」「エラー」「0件」「投稿一覧」を出し分ける */
  const renderPosts = () => {
    if (isLoadingPosts) {
      return <p className="post-status">読み込み中です…</p>
    }

    if (fetchStatus === 'error') {
      return (
        <div className="post-status post-status-error">
          <p>{fetchErrorMessage}</p>
          <button
            type="button"
            className="post-retry-button"
            onClick={() => fetchPosts()}
          >
            再試行
          </button>
        </div>
      )
    }

    // 0件は通信の失敗ではなく取得が成功した結果のため、エラーとは別の文言で伝える
    if (posts.length === 0) {
      return <p className="post-status">投稿がまだありません。</p>
    }

    return (
      <div>
        {posts.map((post) => (
          <PostItem
            key={post.id}
            post={post}
            loggedInUserId={loggedInUserId}
            onRefresh={fetchPosts}
          />
        ))}
      </div>
    )
  }

  return (
    <SidebarLayout loggedInUserName={loggedInUserName}>
      {/* 新規投稿フォーム */}
      <PostForm onSubmit={submitPost} />

      {/* 投稿一覧 */}
      {renderPosts()}

      {/* ページ送り（投稿を表示できているときだけ出し、読み込み中やエラー時に古い件数を見せない） */}
      {hasPosts && <Pagination meta={paginationMeta} onMovePage={fetchPosts} />}
    </SidebarLayout>
  )
}

export default PostList
