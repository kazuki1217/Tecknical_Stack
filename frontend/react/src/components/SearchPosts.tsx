import { useRef, useState } from 'react'
import axios from 'axios'
import { FaChevronLeft, FaChevronRight, FaSearch } from 'react-icons/fa'

import SidebarLayout from './SidebarLayout'
import PostItem from './PostItem'
import '../styles/SearchPosts.css'
import '../styles/PostList.css'
import { buildApiErrorMessage } from '../utils/apiErrorMessage'
import { PaginationMeta, Post } from '../types/post'

/** 検索の実行状態（未検索 / 検索中 / 成功 / 失敗） */
type SearchStatus = 'idle' | 'running' | 'success' | 'error'

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
 * 投稿検索画面コンポーネント。
 *
 * @param loggedInUserId - ログイン中のユーザーID
 * @param loggedInUserName - ログイン中のユーザ名
 * @returns JSX.Element
 */
function SearchPosts({
  loggedInUserId,
  loggedInUserName,
}: {
  loggedInUserId: number | null
  loggedInUserName: string | null
}) {
  const [content, setContent] = useState<string>('') // 検索キーワードやハッシュタグを管理
  const [results, setResults] = useState<Post[]>([]) // 検索にヒットした投稿一覧を管理
  const [paginationMeta, setPaginationMeta] = useState<PaginationMeta>(
    INITIAL_PAGINATION_META,
  ) // 検索結果のページ情報を管理
  const [isComposing, setIsComposing] = useState(false) // IME入力が確定したか否かを管理（日本語入力などで入力を確定したタイミングで検索処理が実行されることを防ぐため）
  const [searchStatus, setSearchStatus] = useState<SearchStatus>('idle') // 検索の実行状況を管理
  const [searchErrorMessage, setSearchErrorMessage] = useState<string | null>(
    null,
  ) // 検索失敗時に画面へ表示する文言を管理
  const lastSearchRef = useRef({ content: '', page: 1 }) // ページ移動・投稿更新・再試行に備えて、最後に実行した検索条件とページを維持（例：入力欄の編集中でも、表示中の検索条件で次ページを取得する）

  /**
   * 指定した検索条件とページに一致する投稿一覧を取得する
   *
   * @param searchContent - 検索キーワードまたはハッシュタグ
   * @param page - 取得するページ番号
   */
  const fetchSearchResults = async (searchContent: string, page: number) => {
    // 失敗しても同じ条件で再試行できるよう、通信の前に検索条件を記録する
    lastSearchRef.current = { content: searchContent, page }

    setSearchStatus('running')
    setSearchErrorMessage(null)

    try {
      const token = localStorage.getItem('token')
      const res = await axios.get(
        `${import.meta.env.VITE_API_BASE_URL}/api/posts/search`,
        {
          headers: {
            Authorization: `Bearer ${token}`,
          },
          params: {
            content: searchContent,
            page,
          },
        },
      )

      // 取得結果を反映してから success にする（順序が逆だと、反映前の空配列で「条件に一致する投稿はありませんでした。」が一瞬表示されるため）
      setResults(res.data.data)
      setPaginationMeta(res.data.meta ?? INITIAL_PAGINATION_META)
      setSearchStatus('success')
    } catch (error) {
      console.error('検索処理に失敗しました:', error)
      setSearchErrorMessage(buildApiErrorMessage(error, '検索に失敗しました。'))
      setSearchStatus('error')
    }
  }

  /** 入力中の検索条件を確定し、1ページ目から検索する */
  const handleSearch = async () => {
    await fetchSearchResults(content, 1)
  }

  /** 最後に実行した検索条件と現在ページを再取得する */
  const refreshSearchResults = async () => {
    await fetchSearchResults(
      lastSearchRef.current.content,
      lastSearchRef.current.page,
    )
  }

  /** 前後ページへ移動 */
  const movePage = async (page: number) => {
    if (page < 1 || page > paginationMeta.last_page) {
      return
    }

    await fetchSearchResults(lastSearchRef.current.content, page)
  }

  const hasResults = searchStatus === 'success' && results.length > 0

  /** 通信状態に応じて「未検索」「検索中」「エラー」「0件」「検索結果」を出し分ける */
  const renderResults = () => {
    // 一度も検索していない状態と、検索して0件だった状態は意味が異なるため文言を分ける
    if (searchStatus === 'idle') {
      return (
        <p className="post-status">
          キーワードまたはタグを入力して検索してください。
        </p>
      )
    }

    if (searchStatus === 'running') {
      return <p className="post-status">検索中です…</p>
    }

    if (searchStatus === 'error') {
      return (
        <div className="post-status post-status-error">
          <p>{searchErrorMessage}</p>
          <button
            type="button"
            className="post-retry-button"
            onClick={refreshSearchResults}
          >
            再試行
          </button>
        </div>
      )
    }

    // 0件は通信の失敗ではなく検索が成功した結果のため、エラーとは別の文言で伝える
    if (results.length === 0) {
      return (
        <p className="post-status">条件に一致する投稿はありませんでした。</p>
      )
    }

    return (
      <div>
        {results.map((post) => (
          <PostItem
            key={post.id}
            post={post}
            loggedInUserId={loggedInUserId}
            onRefresh={refreshSearchResults}
          />
        ))}
      </div>
    )
  }

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
            if (
              e.key === 'Enter' &&
              !isComposing &&
              searchStatus !== 'running'
            ) {
              handleSearch()
            }
          }}
        />
        {/* 検索中は再実行させない（後から返った古い結果で新しい結果が上書きされるのを防ぐため） */}
        <button onClick={handleSearch} disabled={searchStatus === 'running'}>
          <FaSearch />
        </button>
      </div>

      {/* 検索結果の表示 */}
      {renderResults()}

      {/* ページ送り（検索結果を表示できているときだけ出し、検索中やエラー時に古い件数を見せない） */}
      {hasResults && (
        <div className="post-pagination" aria-label="検索結果のページ送り">
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

export default SearchPosts
