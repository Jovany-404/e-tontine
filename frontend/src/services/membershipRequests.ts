import api from "./api";

export interface InvitationPreview {
  tontine_public_id: string;
  name: string;
  type: "family" | "friends" | "work" | "other";
  contribution_amount: number;
  frequency: "daily" | "weekly" | "monthly";
  start_date: string;
  expected_members: number;
  members_count: number;
  manager_name: string;
}

export interface MembershipRequestStatus {
  id: string;
  status: "pending" | "approved" | "rejected";
  created_at: string;
}

export interface ManagerMembershipRequest extends MembershipRequestStatus {
  user: {
    id: string;
    name: string;
    identity_status: "unverified" | "pending" | "verified" | "rejected";
    identity_document_type: "national_id" | "passport" | null;
  };
}

interface ApiResponse<T> {
  data: T;
  meta?: {
    current_page: number;
    last_page: number;
    total: number;
  };
}

export const membershipRequestService = {
  async getInvitation(token: string): Promise<InvitationPreview> {
    const response = await api.get<ApiResponse<InvitationPreview>>(
      `/invitations/${encodeURIComponent(token)}`,
    );

    return response.data.data;
  },

  async getMyRequest(tontinePublicId: string): Promise<MembershipRequestStatus | null> {
    const response = await api.get<ApiResponse<MembershipRequestStatus | null>>(
      `/tontines/${tontinePublicId}/membership-requests/mine`,
    );

    return response.data.data;
  },

  async submit(tontinePublicId: string, invitationToken: string): Promise<MembershipRequestStatus> {
    const response = await api.post<ApiResponse<MembershipRequestStatus>>(
      `/tontines/${tontinePublicId}/membership-requests`,
      { invitation_token: invitationToken },
    );

    return response.data.data;
  },

  async getPendingRequests(
    tontinePublicId: string,
    page = 1,
  ): Promise<{ items: ManagerMembershipRequest[]; page: number; pageCount: number; total: number }> {
    const response = await api.get<ApiResponse<ManagerMembershipRequest[]> & { meta: NonNullable<ApiResponse<ManagerMembershipRequest[]>["meta"]> }>(
      `/tontines/${tontinePublicId}/membership-requests`,
      { params: { page } },
    );
    return {
      items: response.data.data,
      page: response.data.meta.current_page,
      pageCount: response.data.meta.last_page,
      total: response.data.meta.total,
    };
  },

  async getIdentityFile(
    tontinePublicId: string,
    requestId: string,
    kind: "front" | "back" | "profile",
  ): Promise<Blob> {
    const response = await api.get<Blob>(
      `/tontines/${tontinePublicId}/membership-requests/${requestId}/identity/files/${kind}`,
      { responseType: "blob" },
    );
    return response.data;
  },

  async reviewIdentity(
    tontinePublicId: string,
    requestId: string,
    decision: "approve" | "reject",
    reason?: string,
  ): Promise<void> {
    await api.post(
      `/tontines/${tontinePublicId}/membership-requests/${requestId}/identity/${decision}`,
      decision === "reject" ? { reason } : {},
    );
  },

  async approveMembership(tontinePublicId: string, requestId: string): Promise<void> {
    await api.post(
      `/tontines/${tontinePublicId}/membership-requests/${requestId}/approve`,
    );
  },

  async rejectMembership(tontinePublicId: string, requestId: string): Promise<void> {
    await api.post(
      `/tontines/${tontinePublicId}/membership-requests/${requestId}/reject`,
    );
  },

  async resendVerification(): Promise<string> {
    const response = await api.post<{ message: string }>("/auth/email/resend");

    return response.data.message;
  },
};
