package com.example.lsplmobile.ui.main

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.lsplmobile.data.ApiClient
import com.example.lsplmobile.data.ApiResponse
import com.example.lsplmobile.data.DataRepository
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch

class MainScreenViewModel(private val dataRepository: DataRepository) : ViewModel() {
    private val _uiState = MutableStateFlow<MainScreenUiState>(MainScreenUiState.Loading)
    val uiState: StateFlow<MainScreenUiState> = _uiState.asStateFlow()

    init {
        loadData()
    }

    fun loadData() {
        viewModelScope.launch {
            _uiState.value = MainScreenUiState.Loading
            try {
                dataRepository.data.collect { response ->
                    _uiState.value = MainScreenUiState.Success(response)
                }
            } catch (e: Exception) {
                _uiState.value = MainScreenUiState.Error(e)
            }
        }
    }

    fun refreshData() {
        viewModelScope.launch {
            try {
                val response = dataRepository.refresh()
                _uiState.value = MainScreenUiState.Success(response)
            } catch (e: Exception) {
                // Keep existing data if refresh fails, or emit error
                if (_uiState.value !is MainScreenUiState.Success) {
                    _uiState.value = MainScreenUiState.Error(e)
                }
            }
        }
    }

    fun submitLead(
        portal: String,
        name: String,
        email: String,
        phone: String,
        service: String,
        duration: String,
        budget: String,
        message: String,
        leadType: String,
        onResult: (Boolean) -> Unit
    ) {
        viewModelScope.launch {
            val result = ApiClient.submitLead(
                portal = portal,
                name = name,
                email = email,
                phone = phone,
                service = service,
                duration = duration,
                budget = budget,
                message = message,
                leadType = leadType
            )
            onResult(result)
        }
    }
}

sealed interface MainScreenUiState {
    object Loading : MainScreenUiState
    data class Error(val throwable: Throwable) : MainScreenUiState
    data class Success(val data: ApiResponse) : MainScreenUiState
}
