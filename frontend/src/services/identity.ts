import api from "./api";

export type IdentityStatus = "unverified" | "pending" | "verified" | "rejected";
export type IdentityDocumentType = "national_id" | "passport";

export interface IdentityVerification {
  id: string;
  document_type: IdentityDocumentType;
  status: IdentityStatus;
  submitted_at: string | null;
  reviewed_at: string | null;
  rejection_reason: string | null;
}

interface ApiResponse<T> {
  data: T;
}

export const identityService = {
  async getMine(): Promise<IdentityVerification | null> {
    const response = await api.get<ApiResponse<IdentityVerification | null>>("/identity");
    return response.data.data;
  },

  async submit(form: FormData): Promise<IdentityVerification> {
    const response = await api.post<ApiResponse<IdentityVerification>>(
      "/identity/submissions",
      form,
      { headers: { "Content-Type": "multipart/form-data" } },
    );
    return response.data.data;
  },

  async getProfilePhoto(): Promise<Blob> {
    const response = await api.get<Blob>("/identity/profile-photo", {
      responseType: "blob",
    });
    return response.data;
  },
};
