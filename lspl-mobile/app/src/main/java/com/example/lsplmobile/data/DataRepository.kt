package com.example.lsplmobile.data

import android.util.Log
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.flow

interface DataRepository {
  val data: Flow<ApiResponse>
  suspend fun refresh(): ApiResponse
}

class DefaultDataRepository : DataRepository {
  private val tag = "DefaultDataRepository"

  override val data: Flow<ApiResponse> = flow {
    Log.d(tag, "Flow collection started. Fetching from API...")
    try {
      val response = ApiClient.getApiData()
      Log.d(tag, "API data fetched successfully! Enterprise services: ${response.enterprise.services.size}")
      emit(response)
    } catch (e: Exception) {
      Log.e(tag, "Failed to fetch API data", e)
      emit(ApiResponse())
    }
  }

  override suspend fun refresh(): ApiResponse {
    Log.d(tag, "Manual refresh triggered...")
    return try {
      val response = ApiClient.getApiData()
      Log.d(tag, "Refresh successful!")
      response
    } catch (e: Exception) {
      Log.e(tag, "Refresh failed", e)
      throw e
    }
  }
}
