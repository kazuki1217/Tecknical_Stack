import { useCallback, useEffect, useRef, useState } from 'react'
import axios from 'axios'
import { FaChevronLeft, FaChevronRight } from 'react-icons/fa'

import SidebarLayout from './SidebarLayout'
import PostForm from './PostForm'
import PostItem from './PostItem'
import { createPostActions } from '../utils/createPostActions'
import { PaginationMeta, Post } from '../types/post'
import '../styles/PostList.css'

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
  const currentPageRef = useRef(INITIAL_PAGINATION_META.current_page) // 削除・更新後に現在ページを再取得するため保持

  /** 投稿一覧を取得 */
  const fetchPosts = useCallback(async (page = currentPageRef.current) => {
    try {
      const token = localStorage.getItem('token')
      const requestPosts = (targetPage: number) =>
        axios.get(`${import.meta.env.VITE_API_BASE_URL}/api/posts`, {
          headers: {
            Authorization: `Bearer ${token}`,
          },
          params: {
            page: targetPage,
          },
        })

      let res = await requestPosts(page)
      let nextMeta = res.data.meta ?? INITIAL_PAGINATION_META
      if (nextMeta.total > 0 && nextMeta.current_page > nextMeta.last_page) {
        // 削除で最終ページが減った場合は、空ページを表示せず最後のページを取り直す
        res = await requestPosts(nextMeta.last_page)
        nextMeta = res.data.meta ?? INITIAL_PAGINATION_META
      }

      setPosts(res.data.data)
      currentPageRef.current = nextMeta.current_page
      setPaginationMeta(nextMeta)
    } catch (error) {
      console.error('投稿一覧の取得に失敗しました:', error)
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

  return (
    <SidebarLayout loggedInUserName={loggedInUserName}>
      {/* 新規投稿フォーム */}
      <PostForm onSubmit={submitPost} />

      {/* 投稿一覧 */}
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

      {/* ページ送り */}
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
          {paginationMeta.total === 0
            ? '0件'
            : `${paginationMeta.from}-${paginationMeta.to} / ${paginationMeta.total}件`}
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
    </SidebarLayout>
  )
}

export default PostList
