import { apiRequest } from './api';

export type VehicleCategory = 'economy' | 'comfort';

export interface FareEstimateRequest {
  pickup_latitude: number;
  pickup_longitude: number;
  destination_latitude: number;
  destination_longitude: number;
  vehicle_category: VehicleCategory;
}

export interface FareEstimate {
  distance_km: number;
  estimated_fare: number;
  estimated_duration_minutes: number;
  currency: string;
  vehicle_category: VehicleCategory;
}

export interface RideRequest {
  rider_id: number;
  pickup_location: string;
  pickup_latitude: number;
  pickup_longitude: number;
  destination_location: string;
  destination_latitude: number;
  destination_longitude: number;
  vehicle_category: VehicleCategory;
}

export interface Ride {
  id: number;
  rider_id: number;
  pickup_location: string;
  destination_location: string;
  vehicle_category: VehicleCategory;
  estimated_fare: number;
  estimated_duration_minutes: number;
  status: string;
  active_assignment?: {
    driver: {
      id: number;
      name: string;
      vehicle_model: string;
      vehicle_number: string;
    };
  } | null;
}

interface ApiResponse<T> {
  success: boolean;
  message?: string;
  data: T;
}

export async function estimateFare(
  payload: FareEstimateRequest,
): Promise<FareEstimate> {
  const response = await apiRequest<ApiResponse<FareEstimate>>(
    '/fare-estimate',
    {
      method: 'POST',
      body: payload,
    },
  );

  return response.data;
}

export async function createRide(
  payload: RideRequest,
  idempotencyKey: string,
): Promise<Ride> {
  const response = await apiRequest<ApiResponse<Ride>>('/rides', {
    method: 'POST',
    body: payload,
    headers: {
      'Idempotency-Key': idempotencyKey,
    },
  });

  return response.data;
}

export async function getRide(id: number): Promise<Ride> {
  const response = await apiRequest<ApiResponse<Ride>>(`/rides/${id}`);

  return response.data;
}

export async function cancelRide(id: number): Promise<Ride> {
  const response = await apiRequest<ApiResponse<Ride>>(
    `/rides/${id}/cancel`,
    {
      method: 'POST',
    },
  );

  return response.data;
}