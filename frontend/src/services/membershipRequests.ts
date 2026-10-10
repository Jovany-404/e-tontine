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

interface ApiResponse<T> {
  data: T;
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

  async resendVerification(): Promise<string> {
    const response = await api.post<{ message: string }>("/auth/email/resend");

    return response.data.message;
  },
};
