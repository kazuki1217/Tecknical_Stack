export interface Tag {
  id: number;
  name: string;
}

export interface Comment {
  id: number;
  content: string;
  created_at: string;
  user: { name: string | null };
}

export interface Post {
  id: number;
  user: { name: string | null };
  content: string;
  created_at: string;
  image_base64?: string | null;
  tags: Tag[];
  comments: Comment[];
}
