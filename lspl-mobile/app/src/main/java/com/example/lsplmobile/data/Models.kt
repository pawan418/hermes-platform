package com.example.lsplmobile.data

import kotlinx.serialization.Serializable

@Serializable
data class ServiceItem(
    val id: Int,
    val title: String,
    val slug: String,
    val description: String,
    val content: String,
    val icon: String,
    val category: String,
    val tech_stack: String,
    val display_order: Int
)

@Serializable
data class IndustryItem(
    val id: Int,
    val title: String,
    val slug: String,
    val description: String,
    val content: String,
    val icon: String,
    val display_order: Int
)

@Serializable
data class BlogItem(
    val id: Int,
    val title: String,
    val slug: String,
    val summary: String,
    val content: String,
    val author: String,
    val image_url: String? = null,
    val status: String,
    val created_at: String
)

@Serializable
data class PortalData(
    val services: List<ServiceItem> = emptyList(),
    val industries: List<IndustryItem> = emptyList(),
    val blogs: List<BlogItem> = emptyList()
)

@Serializable
data class ApiResponse(
    val enterprise: PortalData = PortalData(),
    val academy: PortalData = PortalData(),
    val ai: PortalData = PortalData()
)
