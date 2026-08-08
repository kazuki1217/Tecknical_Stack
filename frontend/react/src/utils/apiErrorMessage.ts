import axios from 'axios'

/**
 * 400 番台のエラーは API が返す message を返し、500 番台は第二引数の文字列を返す
 *
 * @param error - catch で受け取った例外
 * @param fallbackMessage - APIの message を採用しない場合に表示する文言
 * @returns 画面に表示する文言（例: 422応答なら 'コメントは255文字以内で入力してください。'、通信断なら fallbackMessage）
 */
export function buildApiErrorMessage(
  error: unknown,
  fallbackMessage: string,
): string {
  // AxiosError でない場合は、APIの message を採用できないので fallbackMessage を返す
  if (!axios.isAxiosError(error)) {
    return fallbackMessage
  }

  const status = error.response?.status
  const apiMessage = (error.response?.data as { message?: string } | undefined)
    ?.message
  const isClientError = status !== undefined && status >= 400 && status < 500

  return isClientError && apiMessage ? apiMessage : fallbackMessage
}
