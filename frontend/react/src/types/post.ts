export interface Tag {
  id: number;
  name: string;
}

export interface User {
  id: number;
  name: string | null;
}

export interface Comment {
  id: number;
  content: string;
  created_at: string;
  user: User;
}

export interface Post {
  id: number;
  user: User;
  content: string;
  created_at: string;
  image_base64?: string | null;
  tags: Tag[];
  comments: Comment[];
}
