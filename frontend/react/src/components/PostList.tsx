import { useCallback, useEffect, useRef, useState } from 'react'
import axios from 'axios'
import { FaChevronLeft, FaChevronRight } from 'react-icons/fa'

import SidebarLayout from './SidebarLayout'
import PostForm from './PostForm'
import PostItem from './PostItem'
import { createPostActions } from '../utils/createPostActions'
import { PaginationMeta, Post } from '../types/post'
import '../styles/PostList.css'

/** 投稿一覧取得の実行状態（未実行 / 実行中 / 成功 / 失敗） */
type FetchStatus = 'idle' | 'running' | 'success' | 'error'

const INITIAL_PAGINATION_META: PaginationMeta = {
  current_page: 1,
  last_page: 1,
  per_page: 20,
  total: 0,
  from: null,
  to: null,
  has_more_pages: false,
}

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
      const token = localStorage.getItem('token')
      const requestPage = (targetPage: number) =>
        axios.get(`${import.meta.env.VITE_API_BASE_URL}/api/posts`, {
          headers: {
            Authorization: `Bearer ${token}`,
          },
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

      // APIが返す message は 4xx（レート制限など利用者側で対処できるもの）のみ採用する。
      // 5xx や通信断はサーバー内部の事情であり利用者が対処できないため、固定の文言に統一する。
      const status = axios.isAxiosError(error)
        ? error.response?.status
        : undefined
      const apiMessage = axios.isAxiosError(error)
        ? (error.response?.data as { message?: string } | undefined)?.message
        : undefined
      const isClientError =
        status !== undefined && status >= 400 && status < 500

      setFetchErrorMessage(
        isClientError && apiMessage
          ? apiMessage
          : '投稿一覧の取得に失敗しました。',
      )
      setFetchStatus('error')
    }
  }, [])

  useEffect(() => {
    fetchPosts(1)
  }, [fetchPosts])

  const { deletePost, updatePost } = createPostActions(fetchPosts)

  /** 新規投稿を作成 */
  const submitPost = async (
    content: string,
    imageFile: File | null,
    tags: string,
  ) => {
    try {
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

      const token = localStorage.getItem('token')
      await axios.post(
        `${import.meta.env.VITE_API_BASE_URL}/api/posts`,
        formData,
        {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        },
      )
      // 新規投稿は新着順の先頭に表示されるため、1ページ目を再取得する
      await fetchPosts(1)
    } catch (error) {
      console.error('新規投稿の作成に失敗しました:', error)
    }
  }

  /** 前後ページへ移動 */
  const movePage = async (page: number) => {
    if (page < 1 || page > paginationMeta.last_page) {
      return
    }

    await fetchPosts(page)
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
            onDelete={deletePost}
            onUpdate={updatePost}
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
      {hasPosts && (
        <div className="post-pagination" aria-label="投稿一覧のページ送り">
          <button
            className="post-pagination-button"
            type="button"
            onClick={() => movePage(paginationMeta.current_page - 1)}
            disabled={paginationMeta.current_page <= 1}
            aria-label="前のページ"
          >
            <FaChevronLeft />
          </button>
          <span className="post-pagination-status">
            {`${paginationMeta.from}-${paginationMeta.to} / ${paginationMeta.total}件`}
            <span className="post-pagination-page">
              ページ {paginationMeta.current_page} / {paginationMeta.last_page}
            </span>
          </span>
          <button
            className="post-pagination-button"
            type="button"
            onClick={() => movePage(paginationMeta.current_page + 1)}
            disabled={!paginationMeta.has_more_pages}
            aria-label="次のページ"
          >
            <FaChevronRight />
          </button>
        </div>
      )}
    </SidebarLayout>
  )
}

export default PostList
